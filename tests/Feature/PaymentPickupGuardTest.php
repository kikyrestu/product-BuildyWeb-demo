<?php

namespace Tests\Feature;

use App\Models\PaymentOption;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentPickupGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $appStorage = storage_path('app');
        if (! is_dir($appStorage)) {
            mkdir($appStorage, 0777, true);
        }

        file_put_contents(storage_path('app/install.lock'), '{"installed_at":"2026-03-19 00:00:00"}');
    }

    public function test_order_cannot_be_picked_up_without_payment_method(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-910',
            'customer_id' => null,
            'cashier_id' => $cashier->id,
            'voucher_id' => null,
            'status' => 'selesai',
            'payment_status' => 'paid',
            'payment_option_id' => null,
            'total_amount' => 10000,
            'discount_amount' => 0,
            'final_amount' => 10000,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('orders.status.set', $transaction), [
                'status' => 'diambil',
            ]);

        $response->assertSessionHasErrors(['tracking']);
        $this->assertSame('selesai', (string) $transaction->fresh()?->status);
    }

    public function test_order_cannot_be_picked_up_with_transfer_without_proof(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transfer = PaymentOption::query()->create([
            'type' => 'transfer',
            'label' => 'Transfer BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-911',
            'customer_id' => null,
            'cashier_id' => $cashier->id,
            'voucher_id' => null,
            'status' => 'selesai',
            'payment_status' => 'paid',
            'payment_option_id' => $transfer->id,
            'payment_proof_path' => null,
            'total_amount' => 12000,
            'discount_amount' => 0,
            'final_amount' => 12000,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('orders.status.set', $transaction), [
                'status' => 'diambil',
            ]);

        $response->assertSessionHasErrors(['tracking']);
        $this->assertSame('selesai', (string) $transaction->fresh()?->status);
    }

    public function test_transfer_payment_requires_proof_upload_on_payment_update(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transfer = PaymentOption::query()->create([
            'type' => 'transfer',
            'label' => 'Transfer BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-912',
            'customer_id' => null,
            'cashier_id' => $cashier->id,
            'voucher_id' => null,
            'status' => 'selesai',
            'payment_status' => 'unpaid',
            'payment_option_id' => null,
            'total_amount' => 15000,
            'discount_amount' => 0,
            'final_amount' => 15000,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('orders.payment.update', $transaction), [
                'payment_status' => 'paid',
                'payment_option_id' => $transfer->id,
            ]);

        $response->assertSessionHasErrors(['tracking']);
    }

    public function test_transfer_payment_with_proof_can_be_marked_paid_and_picked_up(): void
    {
        Storage::fake('public');

        $cashier = User::factory()->create(['role' => 'kasir']);
        $transfer = PaymentOption::query()->create([
            'type' => 'transfer',
            'label' => 'Transfer BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $transaction = Transaction::query()->create([
            'receipt_number' => 'TRX-20260319-913',
            'customer_id' => null,
            'cashier_id' => $cashier->id,
            'voucher_id' => null,
            'status' => 'selesai',
            'payment_status' => 'unpaid',
            'payment_option_id' => null,
            'total_amount' => 20000,
            'discount_amount' => 0,
            'final_amount' => 20000,
        ]);

        $paymentResponse = $this
            ->actingAs($cashier)
            ->post(route('orders.payment.update', $transaction), [
                'payment_status' => 'paid',
                'payment_option_id' => $transfer->id,
                'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
            ]);

        $paymentResponse->assertSessionHasNoErrors();

        $updated = $transaction->fresh();
        $this->assertSame('paid', (string) $updated?->payment_status);
        $this->assertSame($transfer->id, (int) $updated?->payment_option_id);
        $this->assertNotNull($updated?->payment_proof_path);

        $statusResponse = $this
            ->actingAs($cashier)
            ->post(route('orders.status.set', $transaction), [
                'status' => 'diambil',
            ]);

        $statusResponse->assertSessionHasNoErrors();
        $this->assertSame('diambil', (string) $transaction->fresh()?->status);
    }
}
