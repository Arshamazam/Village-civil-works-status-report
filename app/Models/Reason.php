<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reason extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'label',
        'parent_id',
        'top_level_group',
        'requires_remark',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_remark' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->label ?? '';
    }

    public function getCategoryAttribute(): string
    {
        return $this->top_level_group ?? '';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Reason::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Reason::class, 'parent_id')->orderBy('sort_order');
    }
}
