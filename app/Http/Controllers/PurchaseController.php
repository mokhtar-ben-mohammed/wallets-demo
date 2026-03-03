<?php

namespace App\Http\Controllers;

use App\Models\TempPayment;
use App\Models\Purchase;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Purchases
 *
 * APIs for handling product purchases via Floosak and Jaib wallets.
 *
 * NOTE for Developers:
 * --------------------
 * The 'items' array in purchase requests is fully flexible.
 * You can pass any array of objects with 'id', 'name', 'quantity', and 'price' as json array[{"id":1,"name":"Samsung Galaxy S24","quantity":1,"price":5000},{"id":2,"name":"Apple iPhone 15","quantity":1,"price":7000}].
 * There is no real products table — this is purely for simulation/testing.
 */
class PurchaseController extends Controller
{
    use ApiResponse;

    private array $validJaibCodes = ['1112', '1113', '1114', '1115', '1116'];

    /**
     * Create Purchase
     *
     * Initialize a purchase using selected wallet.
     *
     * @authenticated
     *
     * @bodyParam wallet string required Wallet name. Must be floosak or jaib. Example: jaib
     * @bodyParam amount number required Purchase amount (less than 30000). Example: 100
     * @bodyParam items array required List of items (any format). Example: [{"id":1,"name":"Product 1","quantity":2,"price":50}]
     * @bodyParam code string Required if wallet=jaib. Must be one of: 1112, 1113, 1114, 1115, 1116. Example: 1112
     *
     * @response 200 {
     *   "isSuccess": true,
     *   "message": "Purchase completed successfully",
     *   "data": { "reference": "20260303153000123", "items": [{"id":1,"name":"Product 1","quantity":2,"price":50}] }
     * }
     *
     * @response 400 {
     *   "isSuccess": false,
     *   "message": "Invalid wallet selected",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "isSuccess": false,
     *   "message": "Invalid Jaib code. Allowed codes: 1112, 1113, 1114, 1115, 1116.",
     *   "data": null
     * }
     */
    public function purchase(Request $request)
    {
        Validator::make($request->all(), [
            'wallet' => 'required|string|in:floosak,jaib',
            'amount' => 'required|numeric|min:1|max:29999',
            'items'  => 'required_if:wallet,jaib|json|min:1',
            'code'   => 'required_if:wallet,jaib|in:' . implode(',', $this->validJaibCodes),
        ], [
            'amount.max' => 'Maximum allowed amount is 29999 YER.',
            'code.in' => 'Invalid Jaib code. Allowed codes: 1112, 1113, 1114, 1115, 1116.',
        ])->validate();

        switch ($request->wallet) {

            case 'floosak':
                $initInvoice = [
                    'source_wallet_id' => 144,
                    'request_id'      => Carbon::now()->format('YmdHis') . rand(100, 1000),
                    'target_phone'    => Auth::user()->mobile,
                    'amount'          => $request->amount,
                    'purpose'         => 'products payment'
                ];

                $floosak = new FloosakPaymentController();
                $response = $floosak->initPayment($initInvoice);

                if (!$response['isSuccess']) {
                    return $this->error($response['message'] ?? 'Payment failed', 400);
                }

                TempPayment::create([
                    'wallet_name'         => 'floosak',
                    'reference_id'        => $response['referenceId'] ?? null,
                    'wallet_reference_id' => $response['id'] ?? null,
                    'status'              => 'pending',
                    'items'               => $request->items, // store directly as JSON
                ]);

                return $this->success(
                    $response['message'] ?? 'OTP sent successfully',
                    ['wallet_reference_id' => $response['id'] ?? null, 'items' => $request->items]
                );

            case 'jaib':
                $data = [
                    'mobile'       => Auth::user()->mobile,
                    'requestID'    => Carbon::now()->format('YmdHis') . rand(100, 1000),
                    'code'         => $request->code,
                    'amount'       => $request->amount,
                    'currencyCode' => 'YER',
                    'notes'        => ''
                ];

                $jaib = new JaibPaymentController();
                $response = $jaib->payment($data);
             
                 //   return $response;
                if (!$response['isSuccess']) {
                    return $this->error($response['message'] ?? 'Payment failed', 400);
                }

                TempPayment::create([
                    'wallet_name'         => 'jaib',
                    'reference_id'        => $response['request_id'] ?? null,
                    'wallet_reference_id' => $response['referenceId'] ?? null,
                    'status'              => 'completed',
                    'items'               => $request->items, // store directly
                ]);

                $this->completePurchase([
                    'user_id'     => Auth::id(),
                    'reference_id'   => $response['referenceId'] ?? null,
                    'request_id'   => $response['request_id'] ?? null,
                    'total'       => $request->amount,
                    'wallet_name' => 'jaib',
                    'items'       => $request->items,
                ]);

                return $this->success(
                    'Purchase completed successfully',
                    //['reference' => $response['referenceID'] ?? null, 'items' => $request->items]
                );
        }

        return $this->error('Invalid wallet selected', 400);
    }

    /**
     * Confirm OTP
     *
     * Confirm Floosak OTP to complete purchase.
     *
     * @authenticated
     *
     * @bodyParam wallet_reference_id string required Wallet reference ID you got it form previous purchase request. Example: 20260303153000123
     * @bodyParam otp string required OTP code. Example: 123456
     * @bodyParam items array required List of purchased items (same format as purchase request)
     *
     * @response 200 {
     *   "isSuccess": true,
     *   "message": "Purchase completed successfully",
     *   "data": { "reference": "20260303153000123", "items": [{"id":1,"name":"Product 1","quantity":2,"price":50}] }
     * }
     *
     * @response 400 {
     *   "isSuccess": false,
     *   "message": "OTP confirmation is only supported for Floosak",
     *   "data": null
     * }
     *
     * @response 404 {
     *   "isSuccess": false,
     *   "message": "Payment not found",
     *   "data": null
     * }
     */
    public function confirmOTP(Request $request)
    {
        Validator::make($request->all(), [
            'wallet_reference_id' => 'required|string',
            'otp'                 => 'required|string',
            'items'               => 'required|json|min:1',
        ])->validate();

        $tempPayment = TempPayment::where(
            'wallet_reference_id',
            $request->wallet_reference_id
        )->first();

        if (!$tempPayment) {
            return $this->error('Payment not found', 404);
        }

        if ($tempPayment->wallet_name !== 'floosak') {
            return $this->error('OTP confirmation is only supported for Floosak', 400);
        }

        $paymentData = [
            'purchase_id' => $tempPayment->wallet_reference_id,
            'otp'         => $request->otp
        ];

        $floosak = new FloosakPaymentController();
        $response = $floosak->confirmPayment($paymentData);
                    return $response;
        if (!$response['isSuccess']) {
            return $this->error($response['message'] ?? 'OTP confirmation failed', 400);
        }

        $tempPayment->update([
            'status'              => 'completed',
            'wallet_reference_id' => $response['id'] ?? null,
        ]);

        $this->completePurchase([
            'user_id'     => Auth::id(),
            'reference'   => $response['id'] ?? null,
            'total'       => $tempPayment->amount ?? 0,
            'wallet_name' => $tempPayment->wallet_name,
            'items'       => $request->items,
        ]);

        return $this->success(
            'Purchase completed successfully',
            ['reference' => $response['id'] ?? null, 'items' => $request->items]
        );
    }

    /**
     * Complete Purchase
     */
    private function completePurchase(array $data)
    {
        Purchase::create([
            'user_id'     => $data['user_id'],
            'reference_id'   => $data['reference_id'] ?? null,
            'request_id'   => $data['request_id'] ?? null,
            'total'       => $data['total'],
            'wallet_name' => $data['wallet_name'],
            'items'       => $data['items'], // stored as array, no json_encode
            'status'      => 'completed'
        ]);
    }
}