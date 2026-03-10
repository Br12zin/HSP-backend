<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // só cria se não existir
            if (!Schema::hasColumn('videos', 'duration')) {
                $table->integer('duration')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // só remove se existir
            if (Schema::hasColumn('videos', 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
};