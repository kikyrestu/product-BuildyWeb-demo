<?php

namespace Database\Seeders;

use App\Models\WaTemplate;
use Illuminate\Database\Seeder;

class WaTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'template_key' => 'order_created',
                'title' => 'Order Created',
                'content' => 'Halo kak {nama}, order laundry kamu dengan resi {resi} sudah kami terima. Total tagihan: {total}. Status saat ini: {status}.',
                'is_active' => true,
            ],
            [
                'template_key' => 'order_ready',
                'title' => 'Order Ready',
                'content' => 'Halo kak {nama}, cucian dengan resi {resi} sudah selesai dan siap diambil. Total: {total}. Pembayaran: {pembayaran}.',
                'is_active' => true,
            ],
            [
                'template_key' => 'order_picked_up',
                'title' => 'Order Picked Up',
                'content' => 'Terima kasih kak {nama}, pesanan resi {resi} sudah diambil. Sampai ketemu lagi.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $item) {
            WaTemplate::query()->updateOrCreate(
                ['template_key' => $item['template_key']],
                $item
            );
        }
    }
}
