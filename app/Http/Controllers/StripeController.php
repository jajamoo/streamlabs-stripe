<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

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

        if (is_array($customerInfo)) {
            $subscriptionId = $stripeService->createSubscription(
                $customerInfo['customer'],
                $customerInfo['payment_method']
            );
            if ($subscriptionId) {
                return response()->json($subscriptionId);
            }
            else {
                return response()->json([
                    'error' => 'Unable to create subscription',
                ], 500);
            }
        }
        else {
            return response()->json([
                'error' => 'Unable to create customer and attach payment method',
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
        $skipCheck      = $request->input('skip_month_check');

        if ($stripeService->checkSubscriptionAndProrate($subscriptionId, $skipCheck)
            //false param here will enforce the 5th month check
        ) {
            return response()->json([
                'success' => true,
            ]);
        }
        return response()->json([
            'success' => false,
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getTotals(StripeService $stripeService)
    {
        $subscriptionData = $stripeService->calculateSubscriptionChargesByCustomer();
        
        return view('subscriptions.monthly_totals', [
            'customerInvoices' => $subscriptionData['customer_invoices'],
            'nextMonths' => $subscriptionData['next_months'],
            'months' => $subscriptionData['next_months'],
            'usdTotals' => $subscriptionData['usdTotals']
        ]);
    }


    private function getMonths()
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = Carbon::now()->subMonths($i)->format('Y-m');
        }
        return array_reverse($months);
    }
}
