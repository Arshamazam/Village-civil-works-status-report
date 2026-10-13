<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NoProgressSitesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Collection $rows;
    protected string $date;

    public function __construct(Collection $rows, string $date)
    {
        $this->rows = $rows;
        $this->date = $date;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Site Code',
            'Village Name',
            'Tehsil',
            'Package',
            'Phase',
            'Priority',
            'Contractor',
            'Issue Type',
            'Status Description',
            'Idle Days',
            'Blocker Root Cause Category',
            'Idle Reason Code',
            'Physical Progress (%)',
            'Target Completion Date',
            'Remarks / Required Action',
            'Report Date',
        ];
    }

    public function map($row): array
    {
        $dayStatus = (string) ($row['day_status'] ?? '');
        $issueType = match ($dayStatus) {
            'dormant' => 'Dormant (Work Stopped / Idle)',
            'not_reported' => 'Not Reported (No Daily Return)',
            default => 'Zero Activity (No Progress)',
        };

        $progress = isset($row['physical_progress']) ? ((string) $row['physical_progress'] . '%') : '0%';

        return [
            $row['site_code'] ?? '',
            $row['village'] ?? '',
            $row['tehsil'] ?? '',
            $row['package'] ?? '—',
            $row['phase_abbr'] ?? '',
            $row['group_name'] ?? $row['priority_code'] ?? '',
            $row['contractor'] ?? 'Unassigned',
            $issueType,
            $row['status_label'] ?? '',
            $row['idle_days'] ?? 0,
            $row['top_level_reason'] ?? 'Unstated',
            $row['reason_code'] ?? '',
            $progress,
            $row['expected_completion_date'] ?? '',
            $row['remarks'] ?? '',
            $this->date,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'B91C1C'],
                ],
            ],
        ];
    }
}
