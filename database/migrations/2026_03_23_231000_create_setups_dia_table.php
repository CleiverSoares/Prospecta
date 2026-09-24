<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setups_dia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('data');
            $table->string('local');
            $table->string('segmento', 40);
            $table->string('horas', 40);
            $table->decimal('mix_prospeccao', 5, 2);
            $table->timestamps();

            $table->unique(['user_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setups_dia');
    }
};
