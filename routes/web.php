<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CycleController as AdminCycleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SyncBatchController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Pwa\PwaController; 
use App\Http\Controllers\CarnetController;
use App\Http\Controllers\Api\SyncController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- AUTHENTIFICATION ---
Route::get('/', function () { return redirect()->route('agent.login'); });

// Login Admin
Route::get('admin/login', [LoginController::class, 'showAdminLogin'])->name('admin.login');
Route::post('admin/login', [LoginController::class, 'adminLogin']);

// Login Agent (PWA)
Route::get('agent/login', [LoginController::class, 'showAgentLogin'])->name('agent.login');
Route::post('agent/login-submit', [LoginController::class, 'agentLogin'])->name('agent.login.submit');

Route::post('logout', [LoginController::class, 'logout'])->name('logout');


// --- ESPACE ADMINISTRATEUR (WEB) ---
Route::middleware(['auth', 'role:Admin', 'no-cache'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::get('/sync-batches', [SyncBatchController::class, 'index'])->name('sync-batches.index');
    Route::get('/sync-batches/{syncBatch}', [SyncBatchController::class, 'show'])->name('sync-batches.show');
    Route::post('/sync-batches/{syncBatch}/approve', [SyncBatchController::class, 'approve'])->name('sync-batches.approve');
    Route::post('/sync-batches/{syncBatch}/reject', [SyncBatchController::class, 'reject'])->name('sync-batches.reject');
    Route::get('/cycles', [AdminCycleController::class, 'index'])->name('cycles.index');
    Route::patch('/cycles/{cycle}/mark-withdrawn', [AdminCycleController::class, 'markWithdrawn'])->name('cycles.mark-withdrawn');
    Route::patch('/agents/{id}/toggle-sync', [AgentController::class, 'toggleSync'])->name('agents.toggle-sync');
    
    Route::resource('agents', AgentController::class);
    Route::patch('agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])->name('agents.toggleStatus');
    Route::get('agents/export/{format}', [AgentController::class, 'export'])->name('agents.export');

    Route::resource('clients', ClientController::class);
    Route::get('clients/export/{format}', [ClientController::class, 'export'])->name('clients.export');
    Route::get('clients/{client}/export-history', [ClientController::class, 'exportHistory'])->name('clients.exportHistory');

    Route::get('/carnets', [CarnetController::class, 'index'])->name('carnets.index');
    Route::get('/carnets/{id}', [CarnetController::class, 'show'])->name('carnets.show');
    Route::post('/carnets/store', [CarnetController::class, 'store'])->name('carnets.store');
    Route::put('/carnets/{id}', [CarnetController::class, 'update'])->name('carnets.update');
    Route::delete('/carnets/{id}', [CarnetController::class, 'destroy'])->name('carnets.destroy');

    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
});


// --- ESPACE AGENT (PWA / MOBILE) ---

// 1. ROUTE PUBLIQUE : Indispensable pour que le shell se charge hors-connexion
Route::get('/pwa/pointage-shell', [PwaController::class, 'pointageShell'])->name('pwa.shell');

// 2. GROUPE PROTEGE
Route::middleware(['auth', 'role:Agent'])->prefix('pwa')->name('pwa.')->group(function () {
    Route::get('/dashboard', [PwaController::class, 'index'])->name('index');
    Route::get('/carnet', [PwaController::class, 'showCarnets'])->name('carnets');
    Route::get('/clients', [PwaController::class, 'clients'])->name('clients');
    Route::get('/cycles-liste', [PwaController::class, 'cyclesList'])->name('cycles-list');
    Route::get('/collectes-liste', [PwaController::class, 'collectesList'])->name('collectes-list');
    Route::get('/get-initial-data', [PwaController::class, 'getInitialData'])->name('initial-data');
    Route::get('/sync', [PwaController::class, 'showSyncPage'])->name('sync');
    Route::get('/check-sync-permission', [PwaController::class, 'checkPermission'])->name('check-sync-permission');
    
    Route::get('/nouveau-carnet/{client_id}', [CarnetController::class, 'agentCreate'])->name('carnets.create');
    Route::post('/store-carnet', [CarnetController::class, 'store'])->name('carnets.store');

    Route::middleware('throttle:sync')->group(function () {
        Route::post('/lock-sync', [PwaController::class, 'lockSync'])->name('lock-sync');
        Route::post('/sync-data-post', [SyncController::class, 'store'])->name('sync-data-post');
        Route::get('/sync-batches/{syncUuid}/status', [SyncController::class, 'batchStatus'])->name('sync-batches.status');
        Route::post('/sync-batches/{syncUuid}/cancel', [SyncController::class, 'cancelBatch'])->name('sync-batches.cancel');
    });
});

Route::middleware(['auth:sanctum', 'role:Agent', 'throttle:sync'])->post('/sync', [SyncController::class, 'synchroniser']);