<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Stripe\StripeClient;

class CreateStripeCustomersAndSubscriptionsCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-stripe-customers-and-subscriptions-command {email} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the customer, subscription';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Set the API key
//        Stripe::setApiKey(env('STRIPE_SECRET'));
        $stripeClient = new StripeClient(env('STRIPE_SECRET'));
        //Do not delete - customer creation and attach payment method
        $customer     = $stripeClient->customers->create(
            [
                'email' =>  $this->argument('email'),
                'name'  =>  $this->argument('name'),
                'test_clock' => env('STRIPE_TEST_CLOCK')
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
                            $this->info("Subscription with id $subscription->id created for $customer->id for the item with $price->id");

                        } catch (\Exception $exception) {
                            dd($exception->getTrace());
                        }

                    }
                }
            }
        }
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'email' => 'What email to use for Stripe Customer Creation?',
            'name' => 'What name to use for Stripe Customer Creation?',
        ];
    }
}
