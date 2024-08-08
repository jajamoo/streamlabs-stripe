<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stripe\StripeClient;

class StripeController extends Controller
{
    /**
     * @param Request $request
     * @param StripeService $stripeService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createSubscription(Request $request, StripeService $stripeService)
    {
        $customerInfo = $stripeService->createCustomerAndAttachPaymentMethod($request->input('email'), $request->input('name'));
        
        if(is_array($customerInfo)){
            $subscriptionId = $stripeService->createSubscription(
                $customerInfo['customer'],
                $customerInfo['payment_method']
            );
            if ($subscriptionId){
                return response()->json($subscriptionId);
            } else {
                return response()->json([
                    'error' => 'Unable to create subscription'
                ], 500);
            }
        }else {
            return response()->json([
                'error' => 'Unable to create customer and attach payment method'
            ], 500);
        }
    }
    
    /**
     * @param Request $request
     * @param StripeService $stripeService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function checkProration(Request $request, StripeService $stripeService)
    {
        $subscriptionId = $request->input('subscription_id');
        $skipCheck = $request->input('skip_month_check');
        
        if($stripeService->checkSubscriptionAndProrate($subscriptionId, $skipCheck)
        //false param here will enforce the 5th month check
        ) {
            return response()->json([
                'success' => true
            ]);
        }
        return response()->json([
            'success' => false
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getTotals()
    {
        $monthlyData = [];

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
                'emails' => $monthData['email']
            ];
        }, $monthlyData);
        
        return view('subscriptions.monthly_totals', compact('headers', 'data'));
    }
}
