<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\Admin\PhaseController;
use App\Http\Controllers\Admin\PriorityController;
use App\Http\Controllers\Admin\ReasonController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\SiteImportExportController;
use App\Http\Controllers\Admin\TehsilController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\EntryCorrectionController;
use App\Http\Controllers\Field\ReturnController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Review\ReviewController;
use Illuminate\Support\Facades\Route;

// Guest Landing
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password/force-change', [LoginController::class, 'showForceChangeForm'])->name('password.force_change');
    Route::post('/password/force-change', [LoginController::class, 'updateForceChangePassword'])->name('password.force_change.update');

    // Field Officer Daily Returns (FORM-1, FORM-2, FORM-3, FORM-11)
    Route::get('/today', [ReturnController::class, 'index'])->name('field.today');
    Route::get('/returns/{site}/{date?}', [ReturnController::class, 'showForm'])->name('field.entry');
    Route::post('/returns/{site}/{date?}', [ReturnController::class, 'saveReturn'])->name('field.save');
    Route::post('/api/returns/sync', [ReturnController::class, 'batchSync'])->name('field.batch-sync');

    // Tehsil In-Charge Review & Approval (REV-1 to REV-4)
    Route::middleware(['role:Super Admin|HQ Manager|Tehsil In-charge'])->group(function () {
        Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
        Route::post('/review/{return}/approve', [ReviewController::class, 'approve'])->name('review.approve');
        Route::post('/review/{return}/return', [ReviewController::class, 'returnItem'])->name('review.return');
        Route::post('/review/approve-all', [ReviewController::class, 'approveAll'])->name('review.approve-all');
        Route::post('/review/{return}/unlock', [ReviewController::class, 'unlock'])->name('review.unlock');
    });

    // Super Admin Routes (Master Data, Users, Audit, Settings)
    Route::prefix('admin')->name('admin.')->middleware(['role:Super Admin'])->group(function () {
        Route::resource('sites', SiteController::class)->except(['show']);
        Route::post('sites/{site}/complete', [SiteController::class, 'markCompleted'])->name('sites.mark-completed');
        Route::post('sites/{site}/gps', [SiteController::class, 'updateGps'])->name('sites.update-gps');

        Route::resource('phases', PhaseController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('priorities', PriorityController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tehsils', TehsilController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('contractors', ContractorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('reasons', ReasonController::class)->only(['index', 'store', 'update', 'destroy']);

        // Users & Roles
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

        // Audit Trail
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Excel Import / Export
        Route::get('import-export', [SiteImportExportController::class, 'index'])->name('import-export.index');
        Route::post('import-export/preview', [SiteImportExportController::class, 'preview'])->name('import-export.preview');
        Route::post('import-export/commit', [SiteImportExportController::class, 'commit'])->name('import-export.commit');
        Route::get('import-export/export', [SiteImportExportController::class, 'export'])->name('import-export.export');
    });

    // Dashboard & Analytics (DASH-1 to DASH-11)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/details.pdf', [DashboardController::class, 'detailsPdf'])->name('dashboard.details-pdf');
    Route::get('/dashboard/details.xlsx', [DashboardController::class, 'detailsExcel'])->name('dashboard.details-excel');
    Route::get('/dashboard/export/sheet', [DashboardController::class, 'exportDataSheet'])->name('dashboard.export-sheet');
    Route::get('/dashboard/export/no-progress', [DashboardController::class, 'exportNoProgress'])->name('dashboard.export-no-progress');

    // Correct a village's daily entry from the dashboard (or enter one reported by phone)
    Route::middleware(['role:Super Admin|HQ Manager'])->group(function () {
        Route::get('/entries/{site}/{date}/edit', [EntryCorrectionController::class, 'edit'])->name('entries.edit');
        Route::put('/entries/{site}/{date}', [EntryCorrectionController::class, 'update'])->name('entries.update');
    });
    Route::get('/api/dashboard/{date}', [DashboardController::class, 'apiSummary'])->name('dashboard.api');
    Route::get('/sites/{site}', [DashboardController::class, 'showSite'])->name('sites.show');
    Route::get('/gallery', [DashboardController::class, 'gallery'])->name('dashboard.gallery');

    // Reports & PDF Automation (RPT-1 to RPT-4, MAIL-1 to MAIL-8)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.view');
    Route::get('/reports/html/{date}', [ReportController::class, 'showHtml'])->name('reports.html');
    Route::get('/reports/daily/{date}.pdf', [ReportController::class, 'downloadPdf'])->name('reports.pdf');
    Route::post('/reports/send-now', [ReportController::class, 'sendNow'])->name('reports.send-now');
    Route::post('/reports/resend', [ReportController::class, 'resend'])->name('reports.resend');
});
