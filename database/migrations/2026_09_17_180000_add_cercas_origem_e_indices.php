<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cercas_temporarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rotulo')->nullable();
            $table->string('cep_inicio', 8)->nullable();
            $table->string('cep_fim', 8)->nullable();
            $table->json('poligono_geojson')->nullable();
            $table->timestamp('expira_em');
            $table->timestamps();

            $table->index(['user_id', 'expira_em']);
            $table->index(['cep_inicio', 'cep_fim']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('origem_lat', 10, 7)->nullable()->after('cep_base_fim');
            $table->decimal('origem_lng', 10, 7)->nullable()->after('origem_lat');
            $table->string('origem_rotulo')->nullable()->after('origem_lng');
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
            $table->index(['prospecto_id', 'created_at']);
        });

        Schema::table('prospectos', function (Blueprint $table) {
            $table->index(['lat', 'lng']);
            $table->index('is_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('prospectos', function (Blueprint $table) {
            $table->dropIndex(['lat', 'lng']);
            $table->dropIndex(['is_cliente']);
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['prospecto_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['origem_lat', 'origem_lng', 'origem_rotulo']);
        });

        Schema::dropIfExists('cercas_temporarias');
    }
};
