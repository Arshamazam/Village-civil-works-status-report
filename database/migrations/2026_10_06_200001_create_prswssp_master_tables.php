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
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Pilot, Phase-0, Phase-1
            $table->string('code')->unique(); // pilot, phase-0, phase-1
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 1, 2, 3, 4 or A, B, C, D
            $table->string('name'); // e.g. Priority 1, Priority A
            $table->unsignedInteger('target_days'); // 15, 45, 75, 90
            $table->date('deadline');
            $table->date('start_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('colour')->default('#10B981');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tehsils', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Tehsil 01, Taunsa
            $table->string('district')->nullable();
            $table->string('code')->nullable(); // e.g. TAU, T01
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Contractor A
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('code'); // e.g. TAU-01
            $table->foreignId('tehsil_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('site_code')->unique(); // PLT-001 / PH0-001 / PH1-001
            $table->string('village_name');
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tehsil_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_m')->default(500);
            $table->string('status')->default('ongoing'); // ongoing, completed, suspended
            $table->date('completed_on')->nullable();
            $table->boolean('is_operational')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phase_id', 'status']);
            $table->index(['tehsil_id', 'status']);
            $table->index(['priority_id', 'status']);
        });

        Schema::create('reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code'); // C1, C2, P1, etc.
            $table->string('label');
            $table->foreignId('parent_id')->nullable()->constrained('reasons')->nullOnDelete();
            $table->string('top_level_group'); // Contractor, PRMSC, External, VLD's, Govt. Land, Other
            $table->boolean('requires_remark')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('user_tehsil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tehsil_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'tehsil_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('report_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('name');
            $table->string('scope')->default('all'); // all or tehsil_{id}
            $table->string('report_type')->default('daily'); // daily, weekly, alerts
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_recipients');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('user_tehsil');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('reasons');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('contractors');
        Schema::dropIfExists('tehsils');
        Schema::dropIfExists('priorities');
        Schema::dropIfExists('phases');
    }
};
