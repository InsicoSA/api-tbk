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
        Schema::create('transaccion', function (Blueprint $table) {
            $table->string('buy_order')->primary();
            $table->string('sessionId');
            $table->integer('amount');
            $table->string('returnUrl');
            $table->string('callbackUrl');
            $table->string('anularUrl');
            $table->string('token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaccion');
    }
};
