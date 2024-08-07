<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Stripe\Invoice;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\StripeClient;
use Stripe\Subscription;

class StripeController extends Controller
{
    use InteractsWithIO;
    public function createSubscription(Request $request)
    {
        $stripeClient = new StripeClient(env('STRIPE_SECRET'));
        //Do not delete - customer creation and attach payment method
        $customer = $stripeClient->customers->create(
            [
                'email'      => $request->input('email'),
                'name'       => $request->input('name'),
                'test_clock' => env('STRIPE_TEST_CLOCK'),
            ]
        );

        $retrievedPaymentMethod = $stripeClient
            ->paymentMethods
            ->attach('pm_card_visa', ['customer' => $customer->id]);

        $products = $stripeClient->products->all(['limit' => 10])->data;
        $couponId = '';

        foreach ($products as $product) {
            if ($product->name == 'Crossclip') {
                $prices = $stripeClient->prices->all(['limit' => 10]);
                foreach ($prices as $price) {
                    if ($price->lookup_key == 'monthly_crossclip_basic') {
                        $coupons = $stripeClient->coupons->all();
                        foreach ($coupons as $coupon) {
                            if ($coupon->name == '5 Dollar Off for 3 Months') {
                                $couponId = $coupon->id;
                            }
                        }
                        try {
                            $subscription = $stripeClient->subscriptions->create([
                                'customer'               => $customer->id,
                                'items'                  => [['price' => $price->id]],
                                'discounts'              => [['coupon' => $couponId]],
                                'currency'               => 'gbp',
                                'default_payment_method' => $retrievedPaymentMethod->id,
                                'trial_period_days'      => 30,
                            ]);
                        } catch (\Exception $exception) {
                            dd($exception->getTrace());
                        }

                    }
                }
            }
        }
        return response()->json($subscription->id);
    }

    public function upgradeSubscription(Request $request)
    {
        // Set the API key
//        Stripe::setApiKey(env('STRIPE_SECRET'));
        $stripeClient = new StripeClient(env('STRIPE_SECRET'));
        $results      = $stripeClient->customers->all(['limit' => 10000])->data;
//        dd($results);
        foreach ($results as $customer) {
            var_dump($customer->email);
            if ($customer->email == 'moe.fixtures@example.com') {
//                dd('here');
            }
        }
//        $subscriptionId = $request->input('subscription_id');
        // Retrieve the subscription

//        $subscription = $stripeClient->subscriptions->retrieve($subscriptionId);


        // Update the subscription with proration
//        $subscription->items                = [
//            [
//                'id'    => $subscription->items->data[0]->id,
//                'price' => 'price_monthly_crossclip_premium_id',
//            ],
//        ];
//        $subscription->proration_behavior   = 'create_prorations';
//        $subscription->billing_cycle_anchor = strtotime('+15 days');
//        $subscription->update($subscriptionId);
//
//        // Return the updated subscription details
//        return response()->json($subscription);
    }

    public function getCurrentSubscriptionMonth($subscriptionStartDate)
    {
        $startDate   = new DateTime("@$subscriptionStartDate");
        $currentDate = new DateTime();

        $interval = $currentDate->diff($startDate);
        // Calculate the number of complete months since the subscription start date
        $months = ($interval->y * 12) + $interval->m;
        // Add 1 to get the current month number (1-based indexing)

        return $months + 1;
    }

    public function checkProration(Request $request)
    {
        //sub_1PkwK7G7uyebomuyQQ7hTP2c
        Stripe::setApiKey(getenv('STRIPE_SECRET'));

        $stripeClient = new StripeClient(env('STRIPE_SECRET'));

        $subscription = $stripeClient->subscriptions->retrieve('sub_1PkwK7G7uyebomuyQQ7hTP2c');
//        dd($subscription);
        //After running the simulation into January, we're now 5 months in
        $currentMonth = $this->getCurrentSubscriptionMonth($subscription->current_period_start);

        if ($currentMonth == 5) {
            echo "This is the 5th month of the subscription. Performing proration.\n";

            $prices = $stripeClient->prices->all(['limit' => 10])->data;
            foreach ($prices as $price) {
                if ($price->lookup_key == 'monthly_crossclip_premium') {
                    $newPriceId = $price->id;
                }
            }
            foreach ($subscription->items->data as $item) {
                if ($item['price']['lookup_key'] == 'monthly_crossclip_basic') {
                    $subscriptionItemId               = $item['id'];
                    $currentSimulatedSubscriptionDate = new DateTime("@$subscription->current_period_start");
                    $currentSimulatedSubscriptionDay  = $currentSimulatedSubscriptionDate->format('d');

                    //If we're past the 15th, start subscription billing the first day of the next month
                    if ($currentSimulatedSubscriptionDay > 15) {
                        $nextBillingDate = $currentSimulatedSubscriptionDate->modify('first day of next month')->setDate($currentSimulatedSubscriptionDate->format('Y'), $currentSimulatedSubscriptionDate->format('m'), 15);
                    }
                    else {
                        $nextBillingDate = $currentSimulatedSubscriptionDate->setDate($currentSimulatedSubscriptionDate->format('Y'), $currentSimulatedSubscriptionDate->format('m'), 15);
                    }
                    $billingCycleAnchor = $nextBillingDate->getTimestamp();
                    
                    // Update the subscription with the new prorated upgrade
                    Subscription::update($subscription->id, [
                        'billing_cycle_anchor' => 'now',
                        'proration_behavior'   => 'create_prorations',
                        'items'                => [
                            [
                                'id'    => $subscriptionItemId,
                                'price' => $newPriceId,
                            ],
                        ],
                    ]);
                }
            }
//            
//            $upcomingInvoice = $stripeClient->invoices->upcoming(['customer' => $subscription->customer]);
//            $stripeClient->invoices->finalizeInvoice($upcomingInvoice->id);

        }
    }
    
    public function getTotals()
    {
        $monthlyData = [];

        Stripe::setApiKey(getenv('STRIPE_SECRET'));

        $stripeClient = new StripeClient(env('STRIPE_SECRET'));

        $invoices = $stripeClient->invoices->all([
            'subscription' => 'sub_1PkwK7G7uyebomuyQQ7hTP2c',
        ])->data;

        foreach ($invoices as $invoice) {
            $invoiceDate = new DateTime("@$invoice->created");
            $monthYear = $invoiceDate->format('m-Y');
            $customer = $stripeClient->customers->retrieve($invoice->customer);
            $created = date('Y-m', $invoice->created);
            $customer = $customer->email;
            if (!isset($monthlyData[$created])) {
                $monthlyData[$created] = [
                    'total_amount' => 0,
                    'emails' => []
                ];
            }
            
            $monthlyData[$created]['total_amount'] += $invoice->amount_due;
            $monthlyData[$created]['email'][] = $customer;
        }
        $headers = array_keys($monthlyData);
        $data = array_map(function ($monthData) {
            return [
                'total_amount' => number_format($monthData['total_amount'] / 100, 2),
                'emails' => implode(', ', array_unique($monthData['emails']))
            ];
        }, $monthlyData);

//        dd($monthlyTotals);
        return view('subscriptions.monthly_totals', compact('headers', 'data'));

    }
}
