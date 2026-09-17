<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localizacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('precisao', 8, 2)->nullable();
            $table->decimal('velocidade', 8, 2)->nullable();
            $table->decimal('direcao', 6, 2)->nullable();
            $table->timestamp('capturado_em');
            $table->timestamps();

            $table->index(['user_id', 'capturado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localizacoes');
    }
};
