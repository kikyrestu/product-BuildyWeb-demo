<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherValidationService
{
    public function validateCode(?string $code, float $totalAmount): array
    {
        if (! $code) {
            return [
                'is_valid' => false,
                'message' => 'Voucher code is required.',
                'discount_amount' => 0.0,
                'voucher' => null,
            ];
        }

        $voucher = Voucher::query()->where('code', strtoupper(trim($code)))->first();

        if (! $voucher) {
            return [
                'is_valid' => false,
                'message' => 'Voucher not found.',
                'discount_amount' => 0.0,
                'voucher' => null,
            ];
        }

        $today = Carbon::now()->startOfDay();

        if ($voucher->valid_from && Carbon::parse($voucher->valid_from)->startOfDay()->gt($today)) {
            return [
                'is_valid' => false,
                'message' => 'Voucher is not active yet.',
                'discount_amount' => 0.0,
                'voucher' => $voucher,
            ];
        }

        if ($voucher->valid_until && Carbon::parse($voucher->valid_until)->startOfDay()->lt($today)) {
            return [
                'is_valid' => false,
                'message' => 'Voucher has expired.',
                'discount_amount' => 0.0,
                'voucher' => $voucher,
            ];
        }

        if ($voucher->min_transaction_amount && $totalAmount < (float) $voucher->min_transaction_amount) {
            return [
                'is_valid' => false,
                'message' => 'Transaction amount is below voucher minimum.',
                'discount_amount' => 0.0,
                'voucher' => $voucher,
            ];
        }

        if ($voucher->quota) {
            $usageCount = Transaction::query()
                ->where('voucher_id', $voucher->id)
                ->count();

            if ($usageCount >= $voucher->quota) {
                return [
                    'is_valid' => false,
                    'message' => 'Voucher quota has been reached.',
                    'discount_amount' => 0.0,
                    'voucher' => $voucher,
                ];
            }
        }

        $discount = $voucher->type === 'fixed'
            ? (float) $voucher->value
            : ($totalAmount * ((float) $voucher->value / 100));

        if ($voucher->max_discount_amount) {
            $discount = min($discount, (float) $voucher->max_discount_amount);
        }

        $discount = min($discount, $totalAmount);

        return [
            'is_valid' => true,
            'message' => 'Voucher is valid.',
            'discount_amount' => round($discount, 2),
            'voucher' => $voucher,
        ];
    }
}
