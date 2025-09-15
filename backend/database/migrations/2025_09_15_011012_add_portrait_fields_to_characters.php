<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            if (! Schema::hasColumn('characters', 'portrait_status')) {
                $table->string('portrait_status')->default('pending')->after('portrait_url');
            }
            if (! Schema::hasColumn('characters', 'portrait_error')) {
                $table->text('portrait_error')->nullable()->after('portrait_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['portrait_status', 'portrait_error']);
        });
    }
};
