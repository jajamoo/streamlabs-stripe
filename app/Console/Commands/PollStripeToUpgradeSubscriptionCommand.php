<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Subscription;

class PollStripeToUpgradeSubscriptionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:poll-stripe-to-upgrade-subscription-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //sub_1PkwK7G7uyebomuyQQ7hTP2c
        Stripe::setApiKey(getenv('STRIPE_SECRET'));

        $stripeClient = new StripeClient(env('STRIPE_SECRET'));

        $subscription = $stripeClient->subscriptions->retrieve('sub_1PkwK7G7uyebomuyQQ7hTP2c');
//        dd($subscription);
        //After running the simulation into January, we're now 5 months in
        /**
         * Note: I know I could have used the advance test clock API endpoints outlined here:
         * https://docs.stripe.com/api/test_clocks/advance
         * 
         * But in the interest of time, I advanced the time via the Stripe Dashboard
         */
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
        }
    }
}
