<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\StripeClient;
use Stripe\Subscription;

class StripeController extends Controller
{
    public function createSubscription(Request $request)
    {
        // Set the API key
//        Stripe::setApiKey(env('STRIPE_SECRET'));
        $stripeClient = new StripeClient(env('STRIPE_SECRET'));
        //Do not delete - customer creation and attach payment method
        $customer     = $stripeClient->customers->create(
            [
                'email' => $request->input('email'),
                'name'  => $request->input('name'),
//                'test_clock' => env('STRIPE_TEST_CLOCK')
            ]
        );

        $retrievedPaymentMethod = $stripeClient
            ->paymentMethods
            ->attach('pm_card_visa', ['customer' => $customer->id]);

        
        $products = $stripeClient->products->all(['limit' => 10])->data;
        $couponId = '';
        foreach ($products as $product){
            if($product->name == 'Crossclip') {
                $prices = $stripeClient->prices->all(['limit' => 10]);
                foreach ($prices as $price){
                    if ($price->lookup_key == 'monthly_crossclip_basic') {
                        $coupons = $stripeClient->coupons->all();
                        foreach ($coupons as $coupon){
                            if($coupon->name == '5 Dollar Off for 3 Months'){
                                $couponId = $coupon->id;
                            }
                        }
                        try {
                            $subscription = $stripeClient->subscriptions->create([
                                'customer' => $customer->id,
                                'items' => [['price' => $price->id]] ,
                                'discounts' => [['coupon' => $couponId]],
                                'currency' => 'gbp',
                                'default_payment_method' => $retrievedPaymentMethod->id,
                                'trial_period_days' => 30
                            ]);
                        } catch (\Exception $exception) {
                            dd($exception->getTrace());
                        }
                        
                    }
                }
            }
        }
        return response()->json($subscription);
        
//        dd($subs);
//        foreach ($subs as $sub){
//            var_dump($subs);
//        }

        // Create the subscription
//        $subscription = $stripeClient->subscriptions->create([
//            'customer'          => $customer->id,
//            'items'             => [
//                [
//                    'price' => 'price_1Pkd66G7uyebomuyZ0nBSt5b', // Replace 'id' with actual ID
//                ],
//            ],
//            'coupon'            => '5fcMPEn3', // Replace 'id'  with actual ID
//            'trial_period_days' => 30,
//            'currency'          => 'gbp',
//        ]);

        // Return the subscription details
//        return response()->json($subscription);
    }

    public function upgradeSubscription(Request $request)
    {
        // Set the API key
//        Stripe::setApiKey(env('STRIPE_SECRET'));
        $stripeClient = new StripeClient(env('STRIPE_SECRET'));
        $results = $stripeClient->customers->all(['limit' => 10000])->data;
//        dd($results);
        foreach ($results as $customer) {
            var_dump($customer->email);
            if ($customer->email == 'moe.fixtures@example.com'){
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

    public function getCurrentSubscriptionMonth($subscription)
    {
        $currentPeriodStart = $subscription->current_period_start;
        $currentPeriodEnd   = $subscription->current_period_end;

        // Convert timestamps to DateTime objects
        $start = new DateTime("@$currentPeriodStart");
        $end   = new DateTime("@$currentPeriodEnd");

        // Get the current month
        $currentMonth = date('m');

        // Check the month of the current period start
        $startMonth = $start->format('m');
        $endMonth   = $end->format('m');

        return $currentMonth;
    }

    public function checkProration(Request $request)
    {
        $subscriptionId = $request->input('subscription_id');
        // Retrieve the subscription
        $stripeClient = new StripeClient(env('STRIPE_SECRET'));
        $subscription = $stripeClient->subscriptions->retrieve($subscriptionId);


        // Check if it is the 5th month of the subscription
        $subscriptionStartDate = new DateTime("@{$subscription->start_date}");
        $currentDate           = new DateTime();

        $interval      = $subscriptionStartDate->diff($currentDate);
        $monthsElapsed = $interval->m + ($interval->y * 12);

        if ($monthsElapsed == 5) {
            echo "This is the 5th month of the subscription. Performing proration.\n";

            // Perform proration: Update the subscription item to the new price with proration
            $subscriptionItemId = $subscription->items->data[0]->id; // Get the subscription item ID

            $stripeClient->subscriptions->update(
                $subscriptionItemId,
                [
                    'price'              => 'price_1Pkd67G7uyebomuyYYGD1Zv2', // Premium
                    'proration_behavior' => 'create_prorations',
                ]
            );
        }
    }
}
