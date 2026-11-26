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
        Schema::table('daily_returns', function (Blueprint $table) {
            $table->boolean('labour_holiday')->default(false);
            $table->string('signature_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_returns', function (Blueprint $table) {
            $table->dropColumn(['labour_holiday', 'signature_path']);
        });
    }
};
