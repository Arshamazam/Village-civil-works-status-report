<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Priority extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'target_days',
        'deadline',
        'start_date',
        'sort_order',
        'colour',
    ];

    protected function casts(): array
    {
        return [
            'target_days' => 'integer',
            'deadline' => 'date',
            'start_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
