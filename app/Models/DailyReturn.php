<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'report_date',
        'submitted_by',
        'visit_time',
        'latitude',
        'longitude',
        'gps_accuracy_m',
        'distance_from_site_m',
        'geofence_ok',
        'status',
        'labour_count',
        'machinery_count',
        'machinery_types',
        'work_done',
        'labour_holiday',
        'signature_path',
        'reason_id',
        'idle_since',
        'remarks',
        'approval_status',
        'approved_by',
        'approved_at',
        'review_comment',
        'submitted_at',
        'is_late',
        'client_uuid',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
            'gps_accuracy_m' => 'float',
            'distance_from_site_m' => 'float',
            'geofence_ok' => 'boolean',
            'labour_count' => 'integer',
            'machinery_count' => 'integer',
            'machinery_types' => 'array',
            'labour_holiday' => 'boolean',
            'idle_since' => 'date',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(Reason::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReturnPhoto::class);
    }
}
