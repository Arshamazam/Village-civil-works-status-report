<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SitesExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contractor;
use App\Models\Package;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\Site;
use App\Models\Tehsil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SiteImportExportController extends Controller
{
    public function index()
    {
        return view('admin.import_export.index');
    }

    public function export()
    {
        AuditLog::log('exported_sites_excel');
        return Excel::download(new SitesExport, 'PRSWSSP_Sites_Master_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt,xls'],
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        if (count($rows) < 2) {
            return back()->with('error', 'Uploaded sheet is empty or contains no data rows.');
        }

        // Expected header matching
        $dataRows = array_slice($rows, 1);

        $tehsils = Tehsil::all()->keyBy(fn($t) => strtolower(trim($t->name)));
        $phases = Phase::all()->keyBy(fn($p) => strtolower(trim($p->name)));
        $contractors = Contractor::all()->keyBy(fn($c) => strtolower(trim($c->name)));
        $priorities = Priority::all()->keyBy(fn($p) => strtolower(trim($p->name)));

        $previewRows = [];
        $hasErrors = false;

        foreach ($dataRows as $idx => $row) {
            if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                continue;
            }

            $rowNum = $idx + 2;
            $rowErrors = [];

            // Extract fields by position
            $phaseName = trim((string)($row[0] ?? ''));
            $tehsilName = trim((string)($row[1] ?? ''));
            $packageCode = trim((string)($row[2] ?? ''));
            $villageName = trim((string)($row[3] ?? ''));
            $priorityName = trim((string)($row[4] ?? ''));
            $deadlineStr = trim((string)($row[5] ?? ''));
            $contractorName = trim((string)($row[6] ?? ''));
            $status = strtolower(trim((string)($row[7] ?? 'ongoing')));
            $lat = isset($row[8]) && $row[8] !== '' ? (float)$row[8] : null;
            $lng = isset($row[9]) && $row[9] !== '' ? (float)$row[9] : null;

            if (empty($villageName)) {
                $rowErrors[] = 'Village name is required.';
            }

            // Tehsil check
            $tehsil = $tehsils[strtolower($tehsilName)] ?? null;
            if (!$tehsil) {
                $rowErrors[] = "Unknown tehsil: '{$tehsilName}'";
            }

            // Phase check
            $phase = $phases[strtolower($phaseName)]
                ?? Phase::where('code', strtolower($phaseName))->first()
                ?? (in_array(strtolower($phaseName), ['phase-1', 'phase 1', 'pilot', 'pilot phase']) ? Phase::where('code', 'pilot')->first() : null);
            if (!$phase) {
                $rowErrors[] = "Unknown phase: '{$phaseName}'";
            }

            // Priority check
            $priority = $priorities[strtolower($priorityName)] ?? null;
            if ($priorityName !== '' && !$priority) {
                $priority = Priority::where('code', $priorityName)->first();
            }

            // Contractor check
            $contractor = $contractors[strtolower($contractorName)] ?? null;

            // Date validation
            if (!empty($deadlineStr)) {
                try {
                    Carbon::parse($deadlineStr);
                } catch (\Exception $e) {
                    $rowErrors[] = "Invalid deadline format: '{$deadlineStr}'";
                }
            }

            if (!empty($rowErrors)) {
                $hasErrors = true;
            }

            $previewRows[] = [
                'row_number' => $rowNum,
                'phase' => $phaseName,
                'phase_id' => $phase ? $phase->id : null,
                'tehsil' => $tehsilName,
                'tehsil_id' => $tehsil ? $tehsil->id : null,
                'package' => $packageCode ?: 'Not listed',
                'village' => $villageName,
                'priority' => $priorityName,
                'priority_id' => $priority ? $priority->id : null,
                'contractor' => $contractorName,
                'contractor_id' => $contractor ? $contractor->id : null,
                'status' => in_array($status, ['ongoing', 'completed', 'suspended']) ? $status : 'ongoing',
                'lat' => $lat,
                'lng' => $lng,
                'errors' => $rowErrors,
            ];
        }

        session(['site_import_preview' => $previewRows]);

        return view('admin.import_export.preview', compact('previewRows', 'hasErrors'));
    }

    public function commit(Request $request)
    {
        $previewRows = session('site_import_preview');

        if (!$previewRows || empty($previewRows)) {
            return redirect()->route('admin.import-export.index')->with('error', 'No preview data found to import.');
        }

        $imported = 0;
        $updated = 0;

        foreach ($previewRows as $row) {
            if (!empty($row['errors'])) {
                continue;
            }

            $phase = Phase::find($row['phase_id']);
            $phPrefix = ($phase && str_contains(strtolower($phase->name), 'pilot')) ? 'PLT' : 'PH1';
            
            $site = Site::where('village_name', $row['village'])
                ->where('tehsil_id', $row['tehsil_id'])
                ->first();

            $package = null;
            if (!empty($row['package']) && $row['package'] !== 'Not listed') {
                $package = Package::firstOrCreate(
                    ['code' => $row['package']],
                    [
                        'tehsil_id' => $row['tehsil_id'],
                        'phase_id' => $row['phase_id'],
                        'contractor_id' => $row['contractor_id'],
                    ]
                );
            }

            if ($site) {
                $site->update([
                    'phase_id' => $row['phase_id'],
                    'tehsil_id' => $row['tehsil_id'],
                    'package_id' => $package ? $package->id : $site->package_id,
                    'priority_id' => $row['priority_id'] ?? $site->priority_id,
                    'contractor_id' => $row['contractor_id'] ?? $site->contractor_id,
                    'status' => $row['status'],
                    'latitude' => $row['lat'] ?? $site->latitude,
                    'longitude' => $row['lng'] ?? $site->longitude,
                ]);
                $updated++;
            } else {
                $countInPhase = Site::where('phase_id', $row['phase_id'])->count() + 1;
                $siteCode = sprintf('%s-%03d', $phPrefix, $countInPhase);

                Site::create([
                    'site_code' => $siteCode,
                    'village_name' => $row['village'],
                    'phase_id' => $row['phase_id'],
                    'tehsil_id' => $row['tehsil_id'],
                    'package_id' => $package ? $package->id : null,
                    'priority_id' => $row['priority_id'] ?? null,
                    'contractor_id' => $row['contractor_id'] ?? null,
                    'status' => $row['status'],
                    'latitude' => $row['lat'],
                    'longitude' => $row['lng'],
                    'geofence_m' => 500,
                ]);
                $imported++;
            }
        }

        session()->forget('site_import_preview');

        AuditLog::log('imported_sites_excel', null, null, ['imported' => $imported, 'updated' => $updated]);

        return redirect()->route('admin.sites.index')
            ->with('success', "Import completed successfully: {$imported} sites created, {$updated} sites updated.");
    }
}
