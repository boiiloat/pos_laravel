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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('profile_image')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->dateTime('create_date')->nullable();
            $table->string('create_by')->nullable();
            $table->boolean('is_delete')->default(false);
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_by')->nullable();
            $table->timestamps();
    
            // Foreign Key Constraint
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
