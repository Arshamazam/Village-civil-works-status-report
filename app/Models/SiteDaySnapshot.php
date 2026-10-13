<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteDaySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'report_date',
        'day_status',
        'labour',
        'machinery',
        'reason_id',
        'top_level_reason',
        'idle_days',
        'idle_band',
        'thinly_manned',
        'last_working_date',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'labour' => 'integer',
            'machinery' => 'integer',
            'idle_days' => 'integer',
            'thinly_manned' => 'boolean',
            'last_working_date' => 'date',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(Reason::class);
    }
}
