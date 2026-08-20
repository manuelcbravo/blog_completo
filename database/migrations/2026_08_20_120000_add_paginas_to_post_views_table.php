<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * La bitácora deja de ser sólo de publicaciones: también registra las visitas
 * a /manuel y /proyectos, que no tienen publicación detrás. Por eso `post_id`
 * pasa a ser nulo y se guarda además la ruta pedida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_views', function (Blueprint $table): void {
            $table->unsignedBigInteger('post_id')->nullable()->change();
            $table->string('ruta')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('post_views', function (Blueprint $table): void {
            $table->dropColumn('ruta');
        });

        Schema::table('post_views', function (Blueprint $table): void {
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
        });
    }
};
