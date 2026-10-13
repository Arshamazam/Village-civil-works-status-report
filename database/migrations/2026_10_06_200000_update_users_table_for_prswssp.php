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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('designation')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('designation');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->boolean('force_password_change')->default(false)->after('last_login_at');
            $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('force_password_change');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'designation',
                'is_active',
                'last_login_at',
                'force_password_change',
                'failed_login_attempts',
                'locked_until',
            ]);
        });
    }
};
