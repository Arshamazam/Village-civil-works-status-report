<?php

namespace App\Exports;

use App\Models\Site;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SitesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Enumerable
    {
        return Site::with(['phase', 'tehsil', 'package', 'priority', 'contractor'])
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Site Code',
            'Phase',
            'Tehsil',
            'Package',
            'Village',
            'Priority',
            'Deadline',
            'Contractor',
            'Status',
            'Latitude',
            'Longitude',
            'Geofence (m)',
        ];
    }

    public function map($site): array
    {
        return [
            $site->site_code,
            $site->phase->name ?? '',
            $site->tehsil->name ?? '',
            $site->package->code ?? 'Not listed',
            $site->village_name,
            $site->priority->name ?? '',
            $site->priority->deadline ? $site->priority->deadline->format('Y-m-d') : '',
            $site->contractor->name ?? '',
            $site->status,
            $site->latitude,
            $site->longitude,
            $site->geofence_m,
        ];
    }
}
