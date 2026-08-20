<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_recursos_detalles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('id_recurso')->constrained('post_recursos')->cascadeOnDelete();
            $table->text('detalle')->nullable();
            $table->string('recurso_url')->nullable();
            $table->string('nombre_original')->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_recursos_detalles');
    }
};
