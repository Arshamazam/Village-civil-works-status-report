<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CardDetailsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Collection $rows;
    protected string $title;
    protected string $date;

    public function __construct(Collection $rows, string $title, string $date)
    {
        $this->rows = $rows;
        $this->title = $title;
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
            'Contractor',
            'Status',
            'Physical Progress (%)',
            'Labour',
            'Machinery',
            'Idle Days',
            'Root Cause / Reason',
            'Remarks / Action Required',
            'Report Date',
        ];
    }

    public function map($row): array
    {
        $progress = isset($row['physical_progress']) ? ((string) $row['physical_progress'] . '%') : '0%';

        return [
            $row['site_code'] ?? '',
            $row['village'] ?? '',
            $row['tehsil'] ?? '',
            $row['package'] ?? '—',
            $row['phase_abbr'] ?? '',
            $row['contractor'] ?? 'Unassigned',
            $row['status_label'] ?? strtoupper((string) ($row['day_status'] ?? '')),
            $progress,
            $row['labour'] ?? 0,
            $row['machinery'] ?? 0,
            $row['idle_days'] ?? 0,
            $row['top_level_reason'] ?? $row['reason_code'] ?? '',
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
                    'startColor' => ['rgb' => '1E293B'],
                ],
            ],
        ];
    }
}
