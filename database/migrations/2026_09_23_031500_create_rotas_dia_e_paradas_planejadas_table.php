<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotas_dia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('data');
            $table->unsignedSmallInteger('total_paradas')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'data']);
        });

        Schema::create('paradas_planejadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_dia_id')->constrained('rotas_dia')->cascadeOnDelete();
            $table->foreignId('prospecto_id')->constrained('prospectos')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('status', 20)->default('pendente');
            $table->foreignId('visita_id')->nullable()->constrained('visitas')->nullOnDelete();
            $table->timestamps();

            $table->index(['rota_dia_id', 'ordem']);
            $table->index(['rota_dia_id', 'prospecto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paradas_planejadas');
        Schema::dropIfExists('rotas_dia');
    }
};
