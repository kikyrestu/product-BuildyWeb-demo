<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    public function show(string $receipt_number): JsonResponse
    {
        $transaction = Transaction::query()
            ->with(['customer', 'details'])
            ->where('receipt_number', $receipt_number)
            ->first();

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'receipt_number' => $transaction->receipt_number,
                'status' => $transaction->status,
                'payment_status' => $transaction->payment_status,
                'final_amount' => $transaction->final_amount,
                'customer' => $transaction->customer,
                'updated_at' => $transaction->updated_at,
            ],
        ]);
    }
}
