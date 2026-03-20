<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\WaTemplate;
use InvalidArgumentException;

class WaMessageBuilderService
{
    public function buildFromTransaction(Transaction $transaction, string $templateKey, ?string $phoneOverride = null): array
    {
        $template = WaTemplate::query()
            ->where('template_key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            throw new InvalidArgumentException('WA template not found or inactive.');
        }

        $transaction->loadMissing('customer');

        $customerName = $transaction->customer?->name ?? 'Pelanggan';
        $rawPhone = $phoneOverride ?: ($transaction->customer?->phone ?? '');
        $phone = $this->normalizePhone($rawPhone);

        if (! $phone) {
            throw new InvalidArgumentException('Customer phone is missing or invalid.');
        }

        $placeholders = [
            '{nama}' => $customerName,
            '{resi}' => $transaction->receipt_number,
            '{status}' => $transaction->status,
            '{total}' => 'Rp '.number_format((float) $transaction->final_amount, 0, ',', '.'),
            '{pembayaran}' => $transaction->payment_status,
        ];

        $message = strtr($template->content, $placeholders);

        return [
            'template_key' => $template->template_key,
            'title' => $template->title,
            'phone' => $phone,
            'message' => $message,
            'wa_url' => 'https://wa.me/'.$phone.'?text='.rawurlencode($message),
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return '62'.$digits;
    }
}
