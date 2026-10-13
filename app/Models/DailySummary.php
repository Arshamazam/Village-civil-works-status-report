<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'payload' => 'array',
        ];
    }
}
