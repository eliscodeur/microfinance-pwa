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
use App\Http\Controllers\Admin\CategoryTontineController;
use App\Http\Controllers\Admin\BonusController;
use App\Http\Controllers\Pwa\PwaController; 
use App\Http\Controllers\Admin\CarnetController;
use App\Http\Controllers\Admin\CreditController;
use App\Http\Controllers\Api\SyncController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- AUTHENTIFICATION ---
Route::get('/', function () { return redirect()->route('agent.login'); });
Route::get('/refresh-csrf', function () {
    return response()->json(['token' => csrf_token()]);
});
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
    Route::get('/sync-batches', [SyncBatchController::class, 'index'])->name('sync-batches.index')->middleware('can:Valider Synchro');
    Route::get('/sync-batches/{syncBatch}', [SyncBatchController::class, 'show'])->name('sync-batches.show')->middleware('can:Valider Synchro');
    Route::post('/sync-batches/{syncBatch}/approve', [SyncBatchController::class, 'approve'])->name('sync-batches.approve')->middleware('can:Valider Synchro');
    Route::post('/sync-batches/{syncBatch}/reject', [SyncBatchController::class, 'reject'])->name('sync-batches.reject')->middleware('can:Valider Synchro');
    Route::get('/cycles', [AdminCycleController::class, 'index'])->name('cycles.index');
    Route::patch('/cycles/{cycle}/mark-withdrawn', [AdminCycleController::class, 'markWithdrawn'])->name('cycles.mark-withdrawn');
    Route::patch('/agents/{id}/toggle-sync', [AgentController::class, 'toggleSync'])->name('agents.toggle-sync');
    
    Route::resource('agents', AgentController::class)->middleware('can:Gérer Agents');
    Route::patch('agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])->name('agents.toggleStatus');
    Route::get('agents/export/{format}', [AgentController::class, 'export'])->name('agents.export');
    Route::post('agents/{agent}/bonus', [AgentController::class, 'storeBonus'])->name('agents.storeBonus');
    Route::post('agents/{agent}/calculate-commissions', [AgentController::class, 'calculateCommissions'])->name('agents.calculateCommissions');

    Route::resource('bonuses', BonusController::class)->only(['index', 'store', 'destroy'])->middleware('can:Gérer Commissions');

    Route::resource('clients', ClientController::class)->middleware('can:Gérer Clients');
    Route::get('clients/export/{format}', [ClientController::class, 'export'])->name('clients.export');
    Route::get('clients/{client}/export-history', [ClientController::class, 'exportHistory'])->name('clients.exportHistory');

    Route::resource('credits', CreditController::class)->only(['index', 'create', 'store', 'show'])->middleware('can:Gérer Crédits');
    Route::post('credits/{credit}/approve', [CreditController::class, 'approve'])->name('credits.approve');
    Route::post('credits/{credit}/settle-with-tontine', [CreditController::class, 'settleCreditWithTontine'])->name('credits.settle-with-tontine');
    Route::patch('credits/{credit}/payments/{payment}', [CreditController::class, 'updatePayment'])->name('credits.payments.update');

    Route::get('/carnets/get-tontines/{clientId}', [CarnetController::class, 'getTontinesByClient'])->name('carnets.get-tontines');
    Route::get('/carnets/get-by-client/{clientId}', [CarnetController::class, 'getCarnetsByClient'])->name('carnets.get-by-client');
    Route::get('/carnets', [CarnetController::class, 'index'])->name('carnets.index')->middleware('can:Gérer Carnets');
    Route::get('/carnets/{carnet}', [CarnetController::class, 'show'])->name('carnets.show')->middleware('can:Gérer Carnets');
    Route::post('/carnets/store', [CarnetController::class, 'store'])->name('carnets.store')->middleware('can:Gérer Carnets');
    Route::put('/carnets/{carnet}', [CarnetController::class, 'update'])->name('carnets.update')->middleware('can:Gérer Carnets');
    Route::delete('/carnets/{carnet}', [CarnetController::class, 'destroy'])->name('carnets.destroy')->middleware('can:Gérer Carnets');
    Route::post('/carnets/depot', [CarnetController::class, 'storeDepot'])->name('carnets.depot');
    Route::post('/carnets/retrait', [CarnetController::class, 'storeRetrait'])->name('carnets.retrait');
    // Route pour l'attribution manuelle d'un bonus
    Route::post('/bonuses/store', [BonusController::class, 'store'])->name('admin.bonuses.store')->middleware('can:Gérer Commissions');

    // --- NOUVELLES ROUTES POUR LA VALIDATION ---

   // 1. Routes personnalisées (DÉCLARER AVANT LE RESOURCE)
    Route::post('bonuses/bulk-approve', [BonusController::class, 'bulkApprove'])->name('bonuses.bulk-approve');
    Route::post('bonuses/{id}/approve-single', [BonusController::class, 'approveSingle'])->name('bonuses.approve-single');
    Route::delete('bonuses/{id}/reject-single', [BonusController::class, 'rejectSingle'])->name('bonuses.reject-single');

    // 2. Route Resource (Seulement pour index, store et destroy)
    Route::resource('bonuses', BonusController::class)->only(['index', 'store', 'destroy']);
    Route::get('paiements/historique', [BonusController::class, 'history'])->name('bonuses.history')->middleware('can:Gérer Commissions');
    
    Route::resource('roles', RoleController::class)->middleware('can:Gérer Utilisateurs');
    Route::resource('users', UserController::class)->middleware('can:Gérer Utilisateurs');
    // Dans routes/web.php
    Route::resource('categories', CategoryTontineController::class)->middleware('can:Gérer Carnets');
    Route::post('agents/{agent}/reset-pin', [AgentController::class, 'resetPin'])->name('agents.reset-pin');
});


// --- ESPACE AGENT (PWA / MOBILE) ---

// 1. ROUTE PUBLIQUE : Indispensable pour que le shell se charge hors-connexion
Route::get('/pwa/pointage-shell', [PwaController::class, 'pointageShell'])->name('pwa.shell');
Route::post('/pwa/update-pin-hash-setup', [PwaController::class, 'updatePinHash'])->name('pwa.pin.update');
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
    Route::get('/security-pin', [PwaController::class, 'showSecurityPin'])->name('pin');
    Route::get('/check-status/{matricule}', [PwaController::class, 'checkAgentStatus']);
    Route::middleware('throttle:sync')->group(function () {
        Route::post('/lock-sync', [PwaController::class, 'lockSync'])->name('lock-sync');
        Route::post('/sync-data-post', [SyncController::class, 'store'])->name('sync-data-post');
        Route::get('/sync-batches/{syncUuid}/status', [SyncController::class, 'batchStatus'])->name('sync-batches.status');
        Route::post('/sync-batches/{syncUuid}/cancel', [SyncController::class, 'cancelBatch'])->name('sync-batches.cancel');
    });
});

Route::middleware(['auth:sanctum', 'role:Agent', 'throttle:sync'])->post('/sync', [SyncController::class, 'synchroniser']);