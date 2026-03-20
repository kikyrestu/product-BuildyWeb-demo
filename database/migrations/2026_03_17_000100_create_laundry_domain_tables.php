<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->index();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->date('show_until')->nullable();
            $table->timestamps();
        });

        Schema::create('master_items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['service', 'duration', 'addon']);
            $table->string('name');
            $table->decimal('base_price', 14, 2)->default(0);
            $table->string('unit')->nullable();
            $table->boolean('is_percentage_modifier')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percentage']);
            $table->decimal('value', 14, 2);
            $table->decimal('max_discount_amount', 14, 2)->nullable();
            $table->decimal('min_transaction_amount', 14, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->enum('status', ['antrean', 'proses_cuci', 'proses_setrika', 'selesai', 'diambil'])->default('antrean');
            $table->enum('payment_status', ['unpaid', 'paid', 'partial'])->default('unpaid');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('final_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
        });

        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('master_service_id')->constrained('master_items')->cascadeOnDelete();
            $table->decimal('qty', 10, 2)->default(1);
            $table->json('snapshot_data');
            $table->timestamps();
        });

        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type');
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('master_items');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('customers');
    }
};
