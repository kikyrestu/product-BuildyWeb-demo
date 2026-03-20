<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCustomerCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_update_customer_data_from_order_detail(): void
    {
        $cashier = User::factory()->create([
            'role' => 'kasir',
        ]);

        $oldCustomer = Customer::query()->create([
            'name' => 'Lama',
            'phone' => '081200000001',
            'address' => 'Alamat Lama',
        ]);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-900',
            'customer_id' => $oldCustomer->id,
            'cashier_id' => $cashier->id,
            'status' => 'antrean',
            'payment_status' => 'unpaid',
            'total_amount' => 10000,
            'discount_amount' => 0,
            'final_amount' => 10000,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('orders.customer.update', $transaction), [
                'customer_name' => 'Budi Baru',
                'customer_phone' => '081299988877',
                'customer_address' => 'Alamat Baru',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $transaction->refresh();
        $this->assertNotNull($transaction->customer_id);
        $this->assertSame('081299988877', (string) $transaction->customer?->phone);
        $this->assertSame('Budi Baru', (string) $transaction->customer?->name);
    }

    public function test_cashier_can_remove_customer_from_order(): void
    {
        $cashier = User::factory()->create([
            'role' => 'kasir',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Budi',
            'phone' => '081233344455',
        ]);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-901',
            'customer_id' => $customer->id,
            'cashier_id' => $cashier->id,
            'status' => 'antrean',
            'payment_status' => 'unpaid',
            'total_amount' => 5000,
            'discount_amount' => 0,
            'final_amount' => 5000,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->delete(route('orders.customer.remove', $transaction));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNull($transaction->fresh()?->customer_id);
    }

    public function test_cashier_cannot_update_customer_after_order_is_picked_up(): void
    {
        $cashier = User::factory()->create([
            'role' => 'kasir',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Budi Lama',
            'phone' => '081233344499',
        ]);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-902',
            'customer_id' => $customer->id,
            'cashier_id' => $cashier->id,
            'status' => 'diambil',
            'payment_status' => 'paid',
            'total_amount' => 5000,
            'discount_amount' => 0,
            'final_amount' => 5000,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->from(route('orders.show', $transaction))
            ->post(route('orders.customer.update', $transaction), [
                'customer_name' => 'Budi Baru',
                'customer_phone' => '081299911122',
                'customer_address' => 'Alamat Baru',
            ]);

        $response
            ->assertSessionHasErrors(['tracking'])
            ->assertRedirect(route('orders.show', $transaction));

        $this->assertSame('Budi Lama', (string) $transaction->fresh()?->customer?->name);
    }

    public function test_owner_can_update_customer_after_order_is_picked_up(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Lama',
            'phone' => '081200011122',
        ]);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-903',
            'customer_id' => $customer->id,
            'cashier_id' => $owner->id,
            'status' => 'diambil',
            'payment_status' => 'paid',
            'total_amount' => 7000,
            'discount_amount' => 0,
            'final_amount' => 7000,
        ]);

        $response = $this
            ->actingAs($owner)
            ->post(route('orders.customer.update', $transaction), [
                'customer_name' => 'Baru',
                'customer_phone' => '081200022233',
                'customer_address' => 'Alamat Baru',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('Baru', (string) $transaction->fresh()?->customer?->name);
    }
}
