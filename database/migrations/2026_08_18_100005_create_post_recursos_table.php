<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_recursos', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->string('resumen')->nullable();
            $table->longText('contenido')->nullable();
            $table->string('imagen_destacada')->nullable();
            $table->string('estado')->default('borrador')->index();
            $table->timestamp('fecha_publicacion')->nullable()->index();
            $table->unsignedInteger('tiempo_lectura')->default(1);
            $table->unsignedInteger('visitas')->default(0);
            $table->boolean('importante')->default(false);
            $table->text('tags_seo')->nullable();
            $table->string('meta_titulo')->nullable();
            $table->string('meta_descripcion', 500)->nullable();
            $table->string('og_imagen')->nullable();
            $table->foreignId('id_categoria')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('id_autor')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_recursos');
    }
};
