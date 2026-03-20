<?php

namespace Database\Seeders;

use App\Models\PaymentOption;
use Illuminate\Database\Seeder;

class PaymentOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'type' => 'cash',
                'label' => 'Cash Kasir',
                'bank_name' => null,
                'account_name' => null,
                'account_number' => null,
                'qris_image_path' => null,
                'notes' => 'Pembayaran tunai langsung di outlet.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'type' => 'transfer',
                'label' => 'Transfer BCA',
                'bank_name' => 'BCA',
                'account_name' => 'SI Laundry',
                'account_number' => '1234567890',
                'qris_image_path' => null,
                'notes' => 'Konfirmasi transfer via WhatsApp.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'type' => 'qris',
                'label' => 'QRIS Outlet',
                'bank_name' => null,
                'account_name' => null,
                'account_number' => null,
                'qris_image_path' => null,
                'notes' => 'Gunakan QRIS statis yang ditempel di kasir.',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            PaymentOption::query()->updateOrCreate(
                ['label' => $item['label']],
                $item
            );
        }
    }
}