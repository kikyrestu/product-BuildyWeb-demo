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
        Schema::create('payment_options', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['cash', 'transfer', 'qris']);
            $table->string('label');
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('qris_image_path')->nullable();
            $table->string('notes', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('payment_option_id')->nullable()->after('voucher_id')->constrained('payment_options')->nullOnDelete();
            $table->string('payment_note', 255)->nullable()->after('payment_status');
            $table->string('completion_photo_path')->nullable()->after('payment_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_option_id');
            $table->dropColumn(['payment_note', 'completion_photo_path']);
        });

        Schema::dropIfExists('payment_options');
    }
};