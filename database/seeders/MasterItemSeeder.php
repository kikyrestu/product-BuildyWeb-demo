<?php

namespace Database\Seeders;

use App\Models\MasterItem;
use Illuminate\Database\Seeder;

class MasterItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['type' => 'service', 'name' => 'Cuci Kering Lipat', 'base_price' => 7000, 'unit' => 'kg'],
            ['type' => 'service', 'name' => 'Cuci Setrika', 'base_price' => 9000, 'unit' => 'kg'],
            ['type' => 'service', 'name' => 'Dry Clean', 'base_price' => 15000, 'unit' => 'pcs'],

            ['type' => 'duration', 'name' => 'Express 6 Jam', 'base_price' => 5000, 'unit' => null],
            ['type' => 'duration', 'name' => 'Same Day', 'base_price' => 3000, 'unit' => null],
            ['type' => 'duration', 'name' => 'Regular 2 Hari', 'base_price' => 0, 'unit' => null],

            ['type' => 'addon', 'name' => 'Pewangi Premium', 'base_price' => 2000, 'unit' => null],
            ['type' => 'addon', 'name' => 'Antinoda', 'base_price' => 3000, 'unit' => null],
            ['type' => 'addon', 'name' => 'Packing Box', 'base_price' => 4000, 'unit' => null],
        ];

        foreach ($items as $item) {
            MasterItem::query()->updateOrCreate(
                [
                    'type' => $item['type'],
                    'name' => $item['name'],
                ],
                [
                    'base_price' => $item['base_price'],
                    'unit' => $item['unit'],
                    'is_percentage_modifier' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}