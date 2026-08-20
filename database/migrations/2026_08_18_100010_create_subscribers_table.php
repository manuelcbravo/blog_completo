<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('nombre')->nullable();
            $table->string('estado')->default('pendiente')->index();
            $table->string('token', 64)->nullable()->unique();
            $table->string('origen')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->timestamp('baja_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
