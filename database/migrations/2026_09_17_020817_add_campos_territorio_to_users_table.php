<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unidade_id')->nullable()->after('id')->constrained('unidades')->nullOnDelete();
            $table->foreignId('gestor_id')->nullable()->after('unidade_id')->constrained('users')->nullOnDelete();
            $table->string('cep_base_inicio', 8)->nullable()->after('remember_token');
            $table->string('cep_base_fim', 8)->nullable()->after('cep_base_inicio');

            $table->index(['cep_base_inicio', 'cep_base_fim']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidade_id');
            $table->dropConstrainedForeignId('gestor_id');
            $table->dropIndex(['cep_base_inicio', 'cep_base_fim']);
            $table->dropColumn(['cep_base_inicio', 'cep_base_fim']);
        });
    }
};
