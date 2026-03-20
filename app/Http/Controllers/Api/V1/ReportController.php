<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function financial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'payment_status' => ['nullable', 'in:unpaid,paid,partial'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();

        $query = Transaction::query()->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if (! empty($validated['payment_status'])) {
            $query->where('payment_status', $validated['payment_status']);
        }

        $transactions = (clone $query)
            ->with('customer:id,name,phone')
            ->latest('id')
            ->limit(200)
            ->get();

        $summary = [
            'total_orders' => (clone $query)->count(),
            'gross_total' => (float) (clone $query)->sum('total_amount'),
            'discount_total' => (float) (clone $query)->sum('discount_amount'),
            'net_total' => (float) (clone $query)->sum('final_amount'),
            'paid_total' => (float) (clone $query)->where('payment_status', 'paid')->sum('final_amount'),
            'unpaid_total' => (float) (clone $query)->where('payment_status', 'unpaid')->sum('final_amount'),
        ];

        return response()->json([
            'data' => [
                'filters' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'payment_status' => $validated['payment_status'] ?? null,
                ],
                'summary' => $summary,
                'transactions' => $transactions,
            ],
        ]);
    }
}
