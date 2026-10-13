<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'type',
        'tehsil_id',
        'pdf_path',
        'status',
        'recipients_count',
        'sent_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'sent_at' => 'datetime',
            'recipients_count' => 'integer',
        ];
    }

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class);
    }
}
