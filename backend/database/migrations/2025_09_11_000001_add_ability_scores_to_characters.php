<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->smallInteger('str_score')->nullable()->after('portrait_url');
            $table->smallInteger('dex_score')->nullable()->after('str_score');
            $table->smallInteger('con_score')->nullable()->after('dex_score');
            $table->smallInteger('int_score')->nullable()->after('con_score');
            $table->smallInteger('wis_score')->nullable()->after('int_score');
            $table->smallInteger('cha_score')->nullable()->after('wis_score');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['str_score', 'dex_score', 'con_score', 'int_score', 'wis_score', 'cha_score']);
        });
    }
};
