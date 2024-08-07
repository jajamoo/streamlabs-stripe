<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSubscriptionAnalyticsForStripeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-subscription-for-stripe-command';

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
        $this->table($headers, $data);
    }
}
