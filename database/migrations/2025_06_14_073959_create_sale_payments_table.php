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
        // database/migrations/[timestamp]_create_sale_payments_table.php
Schema::create('sale_payments', function (Blueprint $table) {
    $table->id();
    $table->decimal('payment_amount', 12, 2);
    $table->decimal('exchange_rate', 10, 4)->default(1);
    $table->string('payment_method_name');
    $table->foreignId('sale_id')->constrained('sales');
    $table->foreignId('payment_method_id')->constrained('payment_methods');
    $table->timestamp('created_date')->useCurrent();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
