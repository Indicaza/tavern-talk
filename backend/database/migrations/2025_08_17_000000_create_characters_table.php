<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_pc')->default(false);
            $table->uuid('owner_user_id')->nullable();
            $table->string('name');
            $table->string('race');
            $table->string('subrace')->nullable();
            $table->string('class');
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('gender')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('alignment')->nullable();
            $table->string('background')->nullable();
            $table->string('personality_type')->nullable();
            $table->text('bio')->nullable();
            $table->string('short_pitch')->nullable();
            $table->string('portrait_url')->nullable();
            $table->timestamps();
            $table->index(['name', 'race', 'class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
