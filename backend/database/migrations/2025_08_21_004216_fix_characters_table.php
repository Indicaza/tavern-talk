<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Rename columns
            if (Schema::hasColumn('characters', 'species')) {
                $table->renameColumn('species', 'race');
            }
            if (Schema::hasColumn('characters', 'age_years')) {
                $table->renameColumn('age_years', 'age');
            }
            if (Schema::hasColumn('characters', 'primary_class')) {
                $table->renameColumn('primary_class', 'class');
            }
            if (Schema::hasColumn('characters', 'primary_subclass')) {
                $table->renameColumn('primary_subclass', 'subclass');
            }

            // Expand alignment size
            if (Schema::hasColumn('characters', 'alignment')) {
                $table->string('alignment', 255)->change();
            }

            // Add missing roleplay columns if they don’t exist
            if (! Schema::hasColumn('characters', 'personality_type')) {
                $table->string('personality_type')->nullable();
            }
            if (! Schema::hasColumn('characters', 'bio')) {
                $table->text('bio')->nullable();
            }
            if (! Schema::hasColumn('characters', 'short_pitch')) {
                $table->string('short_pitch')->nullable();
            }
            if (! Schema::hasColumn('characters', 'portrait_url')) {
                $table->string('portrait_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->renameColumn('race', 'species');
            $table->renameColumn('age', 'age_years');
            $table->renameColumn('class', 'primary_class');
            $table->renameColumn('subclass', 'primary_subclass');

            $table->string('alignment', 2)->change();

            $table->dropColumn(['personality_type', 'bio', 'short_pitch', 'portrait_url']);
        });
    }
};
