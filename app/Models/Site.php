<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_code',
        'village_name',
        'settlements',
        'phase_id',
        'tehsil_id',
        'package_id',
        'priority_id',
        'contractor_id',
        'latitude',
        'longitude',
        'geofence_m',
        'status',
        'physical_progress',
        'expected_completion_date',
        'completed_on',
        'is_operational',
    ];

    protected function casts(): array
    {
        return [
            'settlements' => 'integer',
            'physical_progress' => 'integer',
            'expected_completion_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_m' => 'integer',
            'completed_on' => 'date',
            'is_operational' => 'boolean',
        ];
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function dailyReturns(): HasMany
    {
        return $this->hasMany(DailyReturn::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SiteDaySnapshot::class);
    }

    public function snapshotOn(?string $date = null): HasOne
    {
        $relation = $this->hasOne(SiteDaySnapshot::class);
        if ($date) {
            $relation->whereDate('report_date', $date);
        }

        return $relation;
    }

    /**
     * The DSM-1 form category this site is listed under: phase-0, priority-a/b/c or remaining (pilot).
     */
    public function dsmCategory(): ?string
    {
        $this->loadMissing(['phase', 'priority']);

        if ($this->phase && ($this->phase->code === 'phase-0' || $this->phase->name === 'Phase-0')) {
            return 'phase-0';
        }

        return match ((string) $this->priority?->code) {
            'A', '1' => 'priority-a',
            'B', '2' => 'priority-b',
            'C', '3' => 'priority-c',
            'D', '4' => 'remaining',
            default => null,
        };
    }
}
