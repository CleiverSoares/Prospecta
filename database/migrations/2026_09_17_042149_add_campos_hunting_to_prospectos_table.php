<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospectos', function (Blueprint $table) {
            $table->string('endereco')->nullable()->after('razao_social');
            $table->string('telefone', 40)->nullable()->after('endereco');
            $table->string('origem', 20)->default('GOOGLE')->after('is_cliente');
            $table->string('google_place_id')->nullable()->unique()->after('origem');
        });
    }

    public function down(): void
    {
        Schema::table('prospectos', function (Blueprint $table) {
            $table->dropUnique(['google_place_id']);
            $table->dropColumn(['endereco', 'telefone', 'origem', 'google_place_id']);
        });
    }
};
