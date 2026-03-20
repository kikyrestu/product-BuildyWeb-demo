<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\MasterItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaundryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_services_endpoint_returns_only_service_items(): void
    {
        MasterItem::query()->create([
            'type' => 'service',
            'name' => 'Cuci Kiloan',
            'base_price' => 7000,
            'unit' => 'Kg',
            'is_percentage_modifier' => false,
            'is_active' => true,
        ]);

        MasterItem::query()->create([
            'type' => 'addon',
            'name' => 'Parfum',
            'base_price' => 3000,
            'unit' => null,
            'is_percentage_modifier' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/master/services');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'service');
    }

    public function test_calculate_endpoint_returns_total_and_final_amount(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $service = MasterItem::query()->create([
            'type' => 'service',
            'name' => 'Cuci Komplit',
            'base_price' => 10000,
            'unit' => 'Kg',
            'is_percentage_modifier' => false,
            'is_active' => true,
        ]);

        $addon = MasterItem::query()->create([
            'type' => 'addon',
            'name' => 'Plastik Premium',
            'base_price' => 2000,
            'unit' => null,
            'is_percentage_modifier' => false,
            'is_active' => true,
        ]);

        Voucher::query()->create([
            'code' => 'HEMAT10',
            'type' => 'percentage',
            'value' => 10,
            'max_discount_amount' => null,
            'min_transaction_amount' => null,
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
            'quota' => null,
        ]);

        $response = $this->postJson('/api/v1/orders/calculate', [
            'voucher_code' => 'HEMAT10',
            'items' => [
                [
                    'service_id' => $service->id,
                    'qty' => 2,
                    'addon_ids' => [$addon->id],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.total_amount', 22000);
        $response->assertJsonPath('data.discount_amount', 2200);
        $response->assertJsonPath('data.final_amount', 19800);
    }

    public function test_checkout_endpoint_creates_transaction_and_details(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        Sanctum::actingAs($cashier);

        $customer = Customer::query()->create([
            'name' => 'Budi',
            'phone' => '08123456789',
            'address' => 'Jl. Mawar',
        ]);

        $service = MasterItem::query()->create([
            'type' => 'service',
            'name' => 'Setrika',
            'base_price' => 8000,
            'unit' => 'Kg',
            'is_percentage_modifier' => false,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/orders/checkout', [
            'cashier_id' => $cashier->id,
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_id' => $service->id,
                    'qty' => 3,
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.total_amount', 24000);
        $response->assertJsonPath('data.details_count', 1);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_details', 1);
    }

    public function test_tracking_endpoint_returns_not_found_for_unknown_receipt(): void
    {
        $response = $this->getJson('/api/v1/tracking/TRX-20260317-999');

        $response->assertNotFound();
    }

    public function test_order_status_can_be_moved_with_valid_transition(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        Sanctum::actingAs($cashier);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260317-001',
            'customer_id' => null,
            'cashier_id' => $cashier->id,
            'voucher_id' => null,
            'status' => 'antrean',
            'payment_status' => 'unpaid',
            'total_amount' => 15000,
            'discount_amount' => 0,
            'final_amount' => 15000,
        ]);

        $response = $this->patchJson('/api/v1/orders/'.$transaction->id.'/status', [
            'status' => 'proses_cuci',
            'user_id' => $cashier->id,
            'note' => 'Mulai dicuci',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'proses_cuci');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'proses_cuci',
        ]);

        $this->assertDatabaseHas('transaction_logs', [
            'transaction_id' => $transaction->id,
            'user_id' => $cashier->id,
            'action_type' => 'updated_status_to_proses_cuci',
            'description' => 'Mulai dicuci',
        ]);
    }

    public function test_order_status_rejects_invalid_transition(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        Sanctum::actingAs($cashier);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260317-002',
            'customer_id' => null,
            'cashier_id' => $cashier->id,
            'voucher_id' => null,
            'status' => 'antrean',
            'payment_status' => 'unpaid',
            'total_amount' => 9000,
            'discount_amount' => 0,
            'final_amount' => 9000,
        ]);

        $response = $this->patchJson('/api/v1/orders/'.$transaction->id.'/status', [
            'status' => 'selesai',
            'user_id' => $cashier->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'antrean',
        ]);
    }
}
