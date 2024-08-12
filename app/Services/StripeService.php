<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Subscription;

class StripeService
{

    private $stripeClient;

    public function __construct()
    {
        $this->stripeClient = new StripeClient(env('STRIPE_SECRET'));
    }

    /**
     * @param string $email
     * @param string $name
     * @return array|bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCustomerAndAttachPaymentMethod(string $email, string $name)
    {
        try {
            $customer      = $this->stripeClient->customers->create(
                [
                    'email'      => $email,
                    'name'       => $name,
                    'test_clock' => env('STRIPE_TEST_CLOCK'),
                ]
            );
            $paymentMethod = $this->stripeClient
                ->paymentMethods
                ->attach('pm_card_visa', ['customer' => $customer->id]);
            Log::info("Customer with ID $customer->id with Payment Method ID $paymentMethod->id created in Stripe");
            return [
                'customer'       => $customer,
                'payment_method' => $paymentMethod,
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    /**
     * @param Customer $customer
     * @param PaymentMethod $retrievedPaymentMethod
     * @return false|string
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createSubscription(Customer $customer, PaymentMethod $retrievedPaymentMethod)
    {
        $products = $this->getProducts();
        $couponId = '';

        foreach ($products as $product) {
            if ($product->name == 'Crossclip') {
                $prices = $this->getPrices();
                foreach ($prices as $price) {
                    if ($price->lookup_key == 'monthly_crossclip_basic') {
                        $coupons = $this->getCoupons();
                        foreach ($coupons as $coupon) {
                            if ($coupon->name == '5 Dollar Off for 3 Months') {
                                $couponId = $coupon->id;
                            }
                        }
                        try {
                            $subscription = $this->stripeClient->subscriptions->create([
                                'customer'               => $customer->id,
                                'items'                  => [['price' => $price->id]],
                                'discounts'              => [['coupon' => $couponId]],
                                'currency'               => 'gbp',
                                'default_payment_method' => $retrievedPaymentMethod->id,
                                'trial_period_days'      => 30,
                            ]);
                            Log::info("Subscription with ID $subscription->id created in Stripe");
                            return $subscription->id;
                        } catch (\Exception $exception) {
                            Log::error($exception->getMessage());
                            return false;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param string $subscriptionId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function checkSubscriptionAndProrate(string $subscriptionId, bool $skipFiveMonths = true)
    {
        try {
            $subscription = $this->stripeClient->subscriptions->retrieve($subscriptionId);
            Log::info("Subscription with ID $subscription->id retrieved from Stripe");
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
        //After running the simulation into January, we're now 5 months in
        $currentMonth = $this->getCurrentSubscriptionMonth($subscription->current_period_start);

        if ($skipFiveMonths || (!$skipFiveMonths && $currentMonth == 5)) {
            $logMessage = (!$skipFiveMonths && $currentMonth == 5) ? "Fifth month contraint honored - this is the 5th month of the subscription. Performing proration.\n" : "Five month check skipped, prorating subscription now.\n";
            Log::info($logMessage);
            return $this->prorateSubscription($subscription);
        }
        else {
            Log::info("Subscription with ID $subscription->id could not be prorated - 5 months has not passed");
            return false;
        }
    }

    private function getCurrentSubscriptionMonth($subscriptionStartDate)
    {
        $startDate   = new DateTime("@$subscriptionStartDate");
        $currentDate = new DateTime();

        $interval = $currentDate->diff($startDate);
        // Calculate the number of complete months since the subscription start date
        $months = ($interval->y * 12) + $interval->m;
        // Add 1 to get the current month number (1-based indexing)
        return $months + 1;
    }

    /**
     * @param Subscription $subscription
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function prorateSubscription(Subscription $subscription)
    {
        $prices = $this->getPrices();
        foreach ($prices as $price) {
            if ($price->lookup_key == 'monthly_crossclip_premium') {
                $newPriceId = $price->id;
            }
        }
        foreach ($subscription->items->data as $item) {
            if ($item['price']['lookup_key'] == 'monthly_crossclip_basic') {
                $subscriptionItemId = $item['id'];
                $billingCycleAnchor = $this->calculateBillingDay($subscription->current_period_start);
                try {
                    // Update the subscription with the new prorated upgrade
                    $updatedSubscription = $this->stripeClient->subscriptions->update($subscription->id, [
                        'billing_cycle_anchor' => 'unchanged',
                        'proration_behavior'   => 'create_prorations',
                        'items'                => [
                            [
                                'id'    => $subscriptionItemId,
                                'price' => $newPriceId,
                            ],
                        ],
                    ]);
                    Log::info("Subscription with ID $subscription->id updated in Stripe: prorated to new premium subscription");
                    return true;
                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());
                    return false;
                }
            }
            else {
                Log::info("Subscription with ID $subscription->id not prorated - it's already a premium subscription");
                return false;
            }
        }
        return false;
    }

    /**
     * @param $subscriptionId
     * @return array
     */
    public function calculateSubscriptionChargesByCustomer()
    {
        $subscriptions = [
            'sub_1PkwK7G7uyebomuyQQ7hTP2c',
            'sub_1Pkd6IG7uyebomuyIByFqLGK',
            'sub_1Pkd68G7uyebomuyhksPelXM',
        ];

        $customerInvoices = [];
        $usdTotals = [];

        foreach ($subscriptions as $subscriptionId) {
            $subscription = $this->getSubscriptionById($subscriptionId);

            if (!$subscription) {
                continue; // Skip if the subscription is not found
            }

            $customer         = $this->getCustomerById($subscription->customer);
            $subscriptionItem = $subscription->items->data[0]; // Assuming one item per subscription
            $product          = $this->getProductById($subscriptionItem->price->product);
            $productName      = $product->name;

            $invoices = $this->getAllInvoices($subscription->id);

            // Collect start of month dates for the last and next 12 months
            $months = [];
            for ($i = 11; $i >= 0; $i--) {
                $months[] = Carbon::now()->subMonths($i)->startOfMonth()->format('Y-m');
            }
            for ($i = 1; $i <= 12; $i++) {
                $months[] = Carbon::now()->addMonths($i)->startOfMonth()->format('Y-m');
            }

            $chargeData  = [];
            $totalAmount = 0;

            // Initialize charge data for each month
            foreach ($months as $month) {
                $chargeData[$month] = 0;
            }

            foreach ($invoices as $invoice) {
                $invoiceMonth = Carbon::createFromTimestamp($invoice->created)->format('Y-m');
                if (in_array($invoiceMonth, $months)) {
                    $charge                    = $invoice->total / 100; // Convert amount to dollars
                    $chargeData[$invoiceMonth] += $charge;
                    $totalAmount               += $charge;

                    // Accumulate USD totals (only if the currency is USD)
                    if (!isset($usdTotals[$invoiceMonth])) {
                        $usdTotals[$invoiceMonth] = 0;
                    }
                    $usdTotals[$invoiceMonth] += $charge;

                }

            }

            $customerInvoices[] = [
                'email'   => $customer->email,
                'product' => $productName,
                'charges' => $chargeData,
                'total'   => $totalAmount,
            ];
        }

        return [
            'customer_invoices' => $customerInvoices,
            'next_months'       => $months,
            'months' => array_keys($usdTotals), // Only include months with charges in the final row
            'usdTotals' => $usdTotals
        ];
    }

    /**
     * @param int $subscriptionCurrentPeriodStart
     * @return int
     */
    private function calculateBillingDay(int $subscriptionCurrentPeriodStart)
    {
        $currentSimulatedSubscriptionDate = new DateTime("@$subscriptionCurrentPeriodStart");
        $currentSimulatedSubscriptionDay  = $currentSimulatedSubscriptionDate->format('d');

        //If we're past the 15th, start subscription billing the first day of the next month
        if ($currentSimulatedSubscriptionDay > 15) {
            $nextBillingDate = $currentSimulatedSubscriptionDate->modify('first day of next month')->setDate($currentSimulatedSubscriptionDate->format('Y'), $currentSimulatedSubscriptionDate->format('m'), 15);
        }
        else {
            $nextBillingDate = $currentSimulatedSubscriptionDate->setDate($currentSimulatedSubscriptionDate->format('Y'), $currentSimulatedSubscriptionDate->format('m'), 15);
        }
        return $nextBillingDate->getTimestamp();
    }

    /**
     * @return array|\Stripe\Price[]|\Stripe\StripeObject[]
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getPrices($limit = 10)
    {
        return $this->stripeClient->prices->all(['limit' => $limit])->data;
    }

    /**
     * @return array|\Stripe\Product[]|\Stripe\StripeObject[]
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getProducts($limit = 10)
    {
        return $this->stripeClient->products->all(['limit' => $limit])->data;
    }

    /**
     * @param $subscriptionId
     * @param $limit
     * @return array|\Stripe\Invoice[]|\Stripe\StripeObject[]
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getAllInvoices($subscriptionId, $limit = 10)
    {
        return $this->stripeClient->invoices->all([
            'limit'        => $limit,
            'subscription' => $subscriptionId,
        ])->data;
    }

    /**
     * @param $productId
     * @return \Stripe\Product
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getProductById($productId)
    {
        return $this->stripeClient->products->retrieve($productId);
    }

    /**
     * @param $subscriptionId
     * @return Subscription
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getSubscriptionById($subscriptionId)
    {
        return $this->stripeClient->subscriptions->retrieve($subscriptionId);
    }

    /**
     * @param $customerId
     * @return Customer
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getCustomerById($customerId)
    {
        return $this->stripeClient->customers->retrieve($customerId);
    }

    /**
     * @param $limit
     * @return \Stripe\Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getCoupons($limit = 100)
    {
        return $this->stripeClient->coupons->all(['limit' => $limit]);
    }

}