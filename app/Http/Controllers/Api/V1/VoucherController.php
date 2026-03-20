<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VoucherValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherValidationService $voucherValidationService)
    {
    }

    public function validateVoucher(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->voucherValidationService->validateCode(
            $validated['code'],
            (float) $validated['total_amount']
        );

        return response()->json([
            'data' => [
                'is_valid' => $result['is_valid'],
                'message' => $result['message'],
                'discount_amount' => $result['discount_amount'],
            ],
        ]);
    }
}
