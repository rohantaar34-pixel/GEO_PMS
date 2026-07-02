<?php

// routes/web.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\MaterialRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==================== GUEST ROUTES ====================
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// ==================== AUTHENTICATED ROUTES ====================
Route::middleware(['auth'])->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==================== PROJECT MONITORING ====================
    Route::middleware('role:employee')->group(function () {
        Route::get('/monitoring/submit', [MonitoringController::class, 'submit'])->name('monitoring.submit');
        Route::get('/monitoring/submit/pulse', [MonitoringController::class, 'employeePulse'])->name('monitoring.submit.pulse');
        Route::post('/monitoring/submit', [MonitoringController::class, 'store'])->name('monitoring.store');

        Route::get('/material-requests/create/pulse', [MaterialRequestController::class, 'employeePulse'])->name('material-requests.create.pulse');
        Route::get('/material-requests/create', [MaterialRequestController::class, 'create'])->name('material-requests.create');
        Route::post('/material-requests', [MaterialRequestController::class, 'store'])->name('material-requests.store');
    });
    Route::get('/monitoring/reports/{report}/photos/{photo}', [MonitoringController::class, 'photo'])->name('monitoring.photos.show');

    // ==================== ADMIN-ONLY ROUTES ====================
    Route::middleware('role:admin')->group(function () {
        // Ledger / Projects
        Route::resource('projects', ProjectController::class)->only(['index', 'show', 'edit', 'update']);
        Route::post('projects/{project}/transactions', [ProjectController::class, 'addTransaction'])
            ->name('projects.transactions.store');
        Route::delete('projects/{project}/transactions/{transaction}', [ProjectController::class, 'deleteTransaction'])
            ->name('projects.transactions.destroy');
        Route::get('projects/{project}/summary', [ProjectController::class, 'summary'])->name('projects.summary');
        Route::get('projects/{project}/report/excel', [ProjectReportController::class, 'exportExcel'])->name('projects.report.excel');
        Route::get('projects/{project}/report/pdf', [ProjectReportController::class, 'exportPdf'])->name('projects.report.pdf');
        Route::get('projects/{project}/report/word', [ProjectReportController::class, 'exportWord'])->name('projects.report.word');
        Route::get('projects/{project}/proof/{transaction}', [ProjectController::class, 'viewProof'])->name('projects.proof');
        Route::get('projects/{project}/transactions/json', [ProjectController::class, 'getTransactionsJson'])->name('projects.transactions.json');
        Route::get('projects/{project}/categories/json', [ProjectController::class, 'getCategorySummary'])->name('projects.categories.json');

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::resource('projects', App\Http\Controllers\Settings\ProjectController::class)->except(['show']);
            Route::resource('users', App\Http\Controllers\Settings\UserController::class)->except(['show', 'create', 'edit']);
        });
    });

    // ==================== ADMIN + OFFICE ENGINEER ROUTES ====================
    Route::middleware('role:admin,office_engineer')->group(function () {
        // Document Tracker
        Route::resource('documents', DocumentController::class);
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
        });

        // Admin Monitoring
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/pulse', [MonitoringController::class, 'adminPulse'])->name('monitoring.pulse');
        Route::post('/monitoring/reports/{report}/approve', [MonitoringController::class, 'approve'])->name('monitoring.reports.approve');
        Route::post('/monitoring/reports/{report}/reject', [MonitoringController::class, 'reject'])->name('monitoring.reports.reject');

        // Inventory
        Route::get('inventory/report/excel', [InventoryReportController::class, 'exportExcel'])->name('inventory.report.excel');
        Route::get('inventory/report/pdf', [InventoryReportController::class, 'exportPdf'])->name('inventory.report.pdf');
        Route::get('inventory/report/word', [InventoryReportController::class, 'exportWord'])->name('inventory.report.word');
        Route::resource('inventory', InventoryController::class)->except(['show', 'create']);
        Route::get('inventory/{inventory}/assign',  [InventoryController::class, 'assign'])->name('inventory.assign');
        Route::post('inventory/{inventory}/assign', [InventoryController::class, 'doAssign'])->name('inventory.doAssign');
        Route::get('inventory/{inventory}/assignments', [InventoryController::class, 'assignments'])->name('inventory.assignments');

        // Material Request Review
        Route::get('/material-requests/pulse', [MaterialRequestController::class, 'reviewPulse'])->name('material-requests.pulse');
        Route::get('/material-requests', [MaterialRequestController::class, 'index'])->name('material-requests.index');
        Route::get('/material-requests/{materialRequest}', [MaterialRequestController::class, 'show'])->name('material-requests.show');
        Route::post('/material-requests/{materialRequest}/approve', [MaterialRequestController::class, 'approve'])->name('material-requests.approve');
        Route::post('/material-requests/{materialRequest}/reject', [MaterialRequestController::class, 'reject'])->name('material-requests.reject');
    });
});

// ==================== FALLBACK ROUTE ====================
Route::fallback(function () {
    abort(404);
});
