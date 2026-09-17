<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo', 20);
            $table->string('cep_inicio', 8)->nullable();
            $table->string('cep_fim', 8)->nullable();
            $table->json('poligono_geojson')->nullable();
            $table->timestamps();

            $table->index(['cep_inicio', 'cep_fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
