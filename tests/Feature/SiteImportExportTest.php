<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SiteImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_export_sites_to_excel(): void
    {
        Excel::fake();
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();

        $response = $this->actingAs($admin)->get('/admin/import-export/export');
        $response->assertStatus(200);

        Excel::assertDownloaded('PRSWSSP_Sites_Master_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function test_admin_can_preview_valid_csv_import(): void
    {
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();

        $csvContent = "Phase,Tehsil,Package,Village,Priority,Deadline,Contractor,Status,Latitude,Longitude\n"
                    . "Phase-1,Taunsa,TAU-01,New Import Village,Priority A,2026-10-20,PRMSC Civil Works Contractor,ongoing,30.5,70.5\n";

        $file = UploadedFile::fake()->createWithContent('sites.csv', $csvContent);

        $response = $this->actingAs($admin)->post('/admin/import-export/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('New Import Village');
        $response->assertSee('Valid');
    }

    public function test_preview_identifies_invalid_tehsil_error(): void
    {
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();

        $csvContent = "Phase,Tehsil,Package,Village,Priority,Deadline,Contractor,Status,Latitude,Longitude\n"
                    . "Phase-1,NonExistentTehsil,TAU-01,Bad Village,Priority A,2026-10-20,PRMSC Civil Works Contractor,ongoing,30.5,70.5\n";

        $file = UploadedFile::fake()->createWithContent('sites.csv', $csvContent);

        $response = $this->actingAs($admin)->post('/admin/import-export/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Unknown tehsil');
    }
}
