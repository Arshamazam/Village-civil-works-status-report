<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_return_id',
        'path',
        'thumb_path',
        'taken_at',
        'latitude',
        'longitude',
        'size_kb',
        'photo_hash',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'size_kb' => 'integer',
        ];
    }

    public function dailyReturn(): BelongsTo
    {
        return $this->belongsTo(DailyReturn::class);
    }
}
