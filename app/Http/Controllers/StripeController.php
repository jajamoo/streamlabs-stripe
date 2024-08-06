<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;

class StripeController extends Controller
{
    public function createSubscription(Request $request)
    {
        // Set the API key
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Create a new customer
        $customer = Customer::create([
            'email' => $request->input('email'),
            'source' => $request->input('source'), // Payment method ID
        ]);

        // Create the subscription
        $subscription = Subscription::create([
            'customer' => $customer->id,
            'items' => [[
                'price' => 'price_monthly_crossclip_basic_id', // Replace 'id' with actual ID
            ]],
            'coupon' => 'coupon_5_off_3_months_id', // Replace 'id'  with actual ID
            'trial_period_days' => 30,
            'currency' => 'gbp',
        ]);

        // Return the subscription details
        return response()->json($subscription);
    }

    public function upgradeSubscription(Request $request)
    {
        // Set the API key
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $subscriptionId = $request->input('email');
        // Retrieve the subscription

        $subscription = Subscription::retrieve($subscriptionId);

        // Update the subscription with proration
        $subscription->items = [[
            'id' => $subscription->items->data[0]->id,
            'price' => 'price_monthly_crossclip_premium_id', 
        ]];
        $subscription->proration_behavior = 'create_prorations';
        $subscription->billing_cycle_anchor = strtotime('+15 days');
        $subscription->save();

        // Return the updated subscription details
        return response()->json($subscription);
    }
}
