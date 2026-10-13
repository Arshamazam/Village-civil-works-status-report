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
        Schema::create('daily_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->time('visit_time')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('gps_accuracy_m', 8, 2)->nullable();
            $table->decimal('distance_from_site_m', 8, 2)->nullable();
            $table->boolean('geofence_ok')->default(true);
            $table->string('status'); // active, dormant, completed
            $table->unsignedInteger('labour_count')->default(0);
            $table->unsignedInteger('machinery_count')->default(0);
            $table->json('machinery_types')->nullable();
            $table->text('work_done')->nullable();
            $table->foreignId('reason_id')->nullable()->constrained('reasons')->nullOnDelete();
            $table->date('idle_since')->nullable();
            $table->text('remarks')->nullable();
            $table->string('approval_status')->default('pending'); // pending, approved, returned
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->uuid('client_uuid')->nullable()->unique();
            $table->timestamps();

            $table->unique(['site_id', 'report_date']);
            $table->index(['report_date', 'approval_status']);
        });

        Schema::create('return_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_return_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('size_kb')->nullable();
            $table->string('photo_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('site_day_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->string('day_status'); // active, dormant, not_reported, completed, holiday
            $table->unsignedInteger('labour')->default(0);
            $table->unsignedInteger('machinery')->default(0);
            $table->foreignId('reason_id')->nullable()->constrained('reasons')->nullOnDelete();
            $table->string('top_level_reason')->nullable(); // Contractor, PRMSC, External, VLD's, Govt. Land, Unstated
            $table->unsignedInteger('idle_days')->default(0);
            $table->string('idle_band')->default('active'); // active, watch, warn, escalate, critical, completed, holiday
            $table->boolean('thinly_manned')->default(false);
            $table->date('last_working_date')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'report_date']);
            $table->index(['report_date', 'day_status']);
            $table->index(['report_date', 'idle_band']);
        });

        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->json('payload');
            $table->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('type')->default('daily'); // daily, weekly, alert
            $table->foreignId('tehsil_id')->nullable()->constrained('tehsils')->nullOnDelete();
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // created, updated, deleted, approved, returned, unlocked, imported, logged_in
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('daily_summaries');
        Schema::dropIfExists('site_day_snapshots');
        Schema::dropIfExists('return_photos');
        Schema::dropIfExists('daily_returns');
    }
};
