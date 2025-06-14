<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->decimal('sub_total', 10, 2);
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('sale_date');
            $table->foreignId('table_id')->nullable()->constrained('tables');
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
        Schema::dropIfExists('sales');
    }
};
