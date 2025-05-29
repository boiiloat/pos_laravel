<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by');
            $table->dateTime('deleted_date')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); // This provides created_at and updated_at
            
            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
        {
        Schema::table('categories', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
        });
    }
    }

    public function down()
    
    {
        Schema::dropIfExists('categories');
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};