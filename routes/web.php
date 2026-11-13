<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AgentPerformanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BonusController;
use App\Http\Controllers\Admin\CarnetController;
use App\Http\Controllers\Admin\cashclosingController;
use App\Http\Controllers\Admin\CashSessionController;
use App\Http\Controllers\Admin\CategoryTontineController;
use App\Http\Controllers\Admin\ChargeController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CollecteController;
use App\Http\Controllers\Admin\CreditConfigurationController;
use App\Http\Controllers\Admin\CreditController;
use App\Http\Controllers\Admin\CycleController as AdminCycleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepenseController;
use App\Http\Controllers\Admin\EmployeAdministratifController;
use App\Http\Controllers\Admin\FonctionController;
use App\Http\Controllers\Admin\JournalCaisseController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PayrollEmployerController;
use App\Http\Controllers\Admin\RecetteController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalaryAdvanceController;
use App\Http\Controllers\Admin\SalaryGridController;
use App\Http\Controllers\Admin\StockCarnetController;
use App\Http\Controllers\Admin\SyncBatchController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PretInstructionController;
use App\Http\Controllers\Pwa\PwaController;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- AUTHENTIFICATION ---
Route::get('/', function () {return redirect()->route('agent.login');});
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
// Route::middleware(['auth', 'role:Admin', 'no-cache'])->prefix('admin')->name('admin.')->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//     Route::get('/profile', [UserController::class, 'profile'])->name('profile');
//     Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
//     Route::get('/sync-batches', [SyncBatchController::class, 'index'])->name('sync-batches.index')->middleware('can:Valider Synchros');
//     Route::get('/sync-batches/{syncBatch}', [SyncBatchController::class, 'show'])->name('sync-batches.show')->middleware('can:Valider Synchros');
//     Route::post('/sync-batches/{syncBatch}/approve', [SyncBatchController::class, 'approve'])->name('sync-batches.approve')->middleware('can:Valider Synchros');
//     Route::post('/sync-batches/{syncBatch}/reject', [SyncBatchController::class, 'reject'])->name('sync-batches.reject')->middleware('can:Valider Synchros');
//     Route::get('/cycles', [AdminCycleController::class, 'index'])->name('cycles.index');
//     Route::get('/agents/{id}/chart-data', [AgentController::class, 'getChartData'])->name('agents.chartData');
//     Route::post('/cycles/store', [AdminCycleController::class, 'store'])->name('cycles.store');
//     Route::post('/collectes/store', [CollecteController::class, 'store'])->name('collectes.store');
//     Route::patch('/cycles/{cycle}/mark-withdrawn', [AdminCycleController::class, 'markWithdrawn'])->name('cycles.mark-withdrawn');
//     Route::patch('/agents/{id}/toggle-sync', [AgentController::class, 'toggleSync'])->name('agents.toggle-sync');
//     Route::get('/agents/sync-status', function () {
//         return response()->json(Agent::pluck('can_sync', 'id'));
//     })->name('agents.sync-status');
//     Route::get('/payroll', [PayrollController::class, 'index'])->name('payrolls.index');
//     Route::get('/payrolls/bulletin/{id}', [PayrollController::class, 'showValidated'])
//         ->name('payrolls.validated');
//     Route::get('/payrolls/bulletin/{id}/employe', [PayrollEmployerController::class, 'showValidated'])
//         ->name('payrolls.employe.validated');
//     Route::put('/payrolls/avances/{avance}/tranches', [PayrollController::class, 'updateTranches'])->name('payrolls.avances.updateTranches');
//     Route::get('/payrolls/preview-details', [PayrollController::class, 'previewDetails'])->name('payrolls.details');
//     // 1. Gestion des Fonctions
//     Route::resource('fonctions', FonctionController::class)->except(['create', 'show', 'edit']);

//     // 2. Gestion du Personnel Administratif
//     Route::resource('employes', EmployeAdministratifController::class);
//     Route::get('/stocks', [StockCarnetController::class, 'index'])->name('stocks.index')->middleware('can:Gérer Carnets');
//     Route::post('/stocks/entree', [StockCarnetController::class, 'storeEntree'])->name('stocks.entree');
//     Route::post('/payroll/store', [PayrollController::class, 'store'])->name('payrolls.store');
//     Route::resource('agents', AgentController::class)->middleware('can:Gérer Agents');
//     Route::patch('agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])->name('agents.toggleStatus');
//     Route::get('agents/export/{format}', [AgentController::class, 'export'])->name('agents.export');
//     Route::post('agents/{agent}/bonus', [AgentController::class, 'storeBonus'])->name('agents.storeBonus');
//     Route::post('agents/{agent}/calculate-commissions', [AgentController::class, 'calculateCommissions'])->name('agents.calculateCommissions');
//     Route::get('/agents-list/{historyUlid}', [AgentController::class, 'getAgentsExceptCurrent'])->name('agents.list.except');
//     Route::post('/carnet-historique/{ulid}/reassign', [CarnetController::class, 'reassign'])->name('carnets.reassign');
//     Route::resource('bonuses', BonusController::class)->only(['index', 'store', 'destroy'])->middleware('can:Gérer Commissions');
//     Route::put('/parametres-tarifs', [CategoryTontineController::class, 'updateTarifEpargne'])->name('parametres-tarifs.update');
//     Route::resource('clients', ClientController::class)->middleware('can:Gérer Clients');
//     Route::get('clients/export/{format}', [ClientController::class, 'export'])->name('clients.export');
//     Route::get('clients/{client}/export-history', [ClientController::class, 'exportHistory'])->name('clients.exportHistory');
//     Route::get('/api/evolution-ventes', [CarnetController::class, 'evolutionVentes'])->name('evolution-ventes');
//     Route::get('/api/repartition-tontines', [CarnetController::class, 'repartitionTontines'])->name('repartition-tontines');
//     Route::resource('credits', CreditController::class)->only(['index', 'create', 'store', 'show'])->middleware('can:Gérer Crédits');
//     Route::post('credits/{credit}/approve', [CreditController::class, 'approve'])->name('credits.approve');
//     Route::post('credits/{credit}/settle-with-tontine', [CreditController::class, 'settleCreditWithTontine'])->name('credits.settle-with-tontine');
//     Route::patch('credits/{credit}/payments/{payment}', [CreditController::class, 'updatePayment'])->name('credits.payments.update');
//     Route::get('/credits/check-pending/{carnetId}', [CreditController::class, 'checkPending'])->name('credits.check-pending');
//     Route::get('/carnets/get-tontines/{clientId}', [CarnetController::class, 'getTontinesByClient'])->name('carnets.get-tontines');
//     Route::get('/carnets/get-available-numbers/{clientId}', [CarnetController::class, 'getAvailableCarnetNumbers'])->name('carnets.get-available-numbers');
//     Route::get('/carnets/get-by-client/{clientId}', [CarnetController::class, 'getCarnetsByClient'])->name('carnets.get-by-client');
//     Route::get('/carnets/get-by-client-credit/{clientId}', [CreditController::class, 'getCarnetsByClient'])->name('carnets.get-by-client-credit');
//     Route::get('/carnets/details/{id}', [CreditController::class, 'getCarnetDetails'])->name('carnets.details');
//     Route::get('/carnets/{type?}', [CarnetController::class, 'index'])->name('carnets.index')->middleware('can:Gérer Carnets')->where('type', 'tontine|compte');
//     Route::prefix('charges')->name('charges.')->group(function () {
//         Route::get('/', [ChargeController::class, 'index'])->name('index');
//         Route::get('/categories/create', [ChargeController::class, 'createCategory'])->name('categories.create');
//         Route::post('/categories', [ChargeController::class, 'storeCategory'])->name('categories.store');
//         Route::patch('/categories/{ulid}/toggle', [ChargeController::class, 'toggleCategoryStatus'])->name('categories.toggle');
//         Route::post('/types', [ChargeController::class, 'storeType'])->name('types.store');
//     });
//     Route::resource('depenses', DepenseController::class)->parameters([
//         'depenses' => 'depense:ulid',
//     ]);
//     Route::get('/caisse/journal', [JournalCaisseController::class, 'index'])->name('caisse.journal');

//     Route::get('/caisse/ouvrir', [CashSessionController::class, 'create'])->name('caisse.ouvrir');
//     Route::post('/caisse/ouvrir', [CashSessionController::class, 'store'])->name('caisse.ouvrir.store');

//     // 3. Clôture de Caisse (Sans middleware pour l'instant, comme demandé)
//     Route::get('/caisse/cloture', [CashClosingController::class, 'index'])->name('caisse.cloture');
//     Route::post('/caisse/cloture', [CashClosingController::class, 'store'])->name('caisse.cloture.store');
//     Route::get('/recettes', [RecetteController::class, 'index'])->name('recettes.index');
//     Route::get('/carnets/{carnet}', [CarnetController::class, 'show'])->name('carnets.show')->middleware('can:Gérer Carnets');
//     Route::post('/carnets/store', [CarnetController::class, 'store'])->name('carnets.store')->middleware('can:Gérer Carnets');
//     Route::put('/carnets/{carnet}', [CarnetController::class, 'update'])->name('carnets.update')->middleware('can:Gérer Carnets');
//     Route::delete('/carnets/{carnet}', [CarnetController::class, 'destroy'])->name('carnets.destroy')->middleware('can:Gérer Carnets');
//     Route::post('/carnets/depot', [CarnetController::class, 'storeDepot'])->name('carnets.depot');
//     Route::post('/carnets/retrait', [CarnetController::class, 'storeRetrait'])->name('carnets.retrait');
//     // Route pour l'attribution manuelle d'un bonus
//     Route::post('/bonuses/store', [BonusController::class, 'store'])->name('admin.bonuses.store')->middleware('can:Gérer Commissions');
//     Route::post('/client-carnets', [ClientController::class, 'storeNumCarnet'])->name('client-carnets.store');

//     // 1. Routes personnalisées (DÉCLARER AVANT LE RESOURCE)
//     Route::post('bonuses/bulk-approve', [BonusController::class, 'bulkApprove'])->name('bonuses.bulk-approve');
//     Route::post('bonuses/{id}/approve-single', [BonusController::class, 'approveSingle'])->name('bonuses.approve-single');

//     Route::post('/agents/{agent}/avances', [SalaryAdvanceController::class, 'storeAgent'])->name('agents.avances.store');

//     Route::get('/payrolls/employes', [PayrollEmployerController::class, 'index'])->name('payrolls.employes.index');
//     Route::get('payrolls/employes/{ulid}', [PayrollEmployerController::class, 'previewDetails'])->name('payrolls.employes.details');
//     Route::post('/payrolls/employes/store', [PayrollEmployerController::class, 'store'])->name('payrolls.employes.store');
//     Route::put('/payrolls/avances/employe/{avance}/tranches', [PayrollEmployerController::class, 'updateTranches'])->name('payrolls.avances.employe.updateTranches');

//     Route::post('/payrolls/employes/{ulid}/payroll/store-brouillon', [PayrollEmployerController::class, 'storeBrouillon'])
//         ->name('payrolls.employe.store-brouillon');
//     Route::post('/employes/{employe}/avances', [SalaryAdvanceController::class, 'storeEmploye'])->name('employes.avances.store');
//     Route::delete('/avances/{id}', [SalaryAdvanceController::class, 'destroy'])->name('avances.destroy');
//     Route::get('/avances/{id}/edit', [SalaryAdvanceController::class, 'edit'])->name('avances.edit');
//     Route::get('/payrolls/avances', [SalaryAdvanceController::class, 'index'])->name('payrolls.avance');
//     Route::put('/avances/{uid}', [SalaryAdvanceController::class, 'update'])->name('avances.update');
//     Route::patch('/payrolls/avance/{id}/valider', [SalaryAdvanceController::class, 'validerAvance'])->name('payrolls.avance.valider');
//     Route::patch('/payrolls/avance/{id}/rejeter', [SalaryAdvanceController::class, 'rejeterAvance'])->name('payrolls.avance.rejeter');
//     // 2. Route Resource (Seulement pour index, store et destroy)
//     Route::resource('bonuses', BonusController::class)->only(['index', 'store', 'destroy']);
//     Route::get('paiements/historique', [BonusController::class, 'history'])->name('bonuses.history')->middleware('can:Gérer Commissions');

//     Route::resource('roles', RoleController::class)->middleware('can:Gérer Utilisateurs');
//     Route::resource('users', UserController::class)->middleware('can:Gérer Utilisateurs');
//     // Dans routes/web.php
//     Route::resource('categories', CategoryTontineController::class)->middleware('can:Gérer Carnets');
//     Route::post('agents/{agent}/reset-pin', [AgentController::class, 'resetPin'])->name('agents.reset-pin');

//     // Routes pour gestion / instruction des prêts (validation, décaissement)
//     Route::get('prets', [PretInstructionController::class, 'index'])->name('prets.index')->middleware('can:Gérer Crédits');
//     Route::get('prets/{id}', [PretInstructionController::class, 'show'])->name('prets.show')->middleware('can:Gérer Crédits');
//     Route::post('prets/{id}/valider', [PretInstructionController::class, 'valider'])->name('prets.valider')->middleware('can:Gérer Crédits');
//     Route::post('prets/{id}/decaisser', [PretInstructionController::class, 'decaisser'])->name('prets.decaisser')->middleware('can:Gérer Crédits');
// });
Route::middleware(['auth', 'role:Admin', 'no-cache'])->prefix('admin')->name('admin.')->group(function () {

    // ==========================================
    // 1. DASHBOARD & PROFIL
    // ==========================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])
        ->name('dashboard.chart-data')
        ->middleware('can:treasury.view');
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->name('audit-logs.index')
        ->middleware('can:audits.view');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])
        ->name('audit-logs.export')
        ->middleware('can:audits.export');

    // ==========================================
    // 2. GESTION DES SESSIONS & CAISSE (Ouverture & Clôture)
    // ==========================================
    Route::get('/caisse/ouvrir', [CashSessionController::class, 'create'])
        ->name('caisse.ouvrir')
        ->middleware('can:treasury.open');
    Route::post('/caisse/ouvrir', [CashSessionController::class, 'store'])
        ->name('caisse.ouvrir.store')
        ->middleware('can:treasury.open');

    // Routes de clôture et journal (protégées par la session ouverte)
    Route::get('/caisse/journal', [JournalCaisseController::class, 'index'])
        ->name('caisse.journal')
        ->middleware('can:treasury.view');
    Route::middleware(['cash.open'])->group(function () {
        Route::get('/caisse/approvisionner', [CashSessionController::class, 'replenish'])
            ->name('caisse.approvisionner')
            ->middleware('can:treasury.replenish');
        Route::post('/caisse/approvisionner', [CashSessionController::class, 'storeReplenishment'])
            ->name('caisse.approvisionner.store')
            ->middleware('can:treasury.replenish');
        Route::get('/caisse/cloture', [CashClosingController::class, 'index'])
            ->name('caisse.cloture')
            ->middleware('can:treasury.close');
        Route::post('/caisse/cloture', [CashClosingController::class, 'store'])
            ->name('caisse.cloture.store')
            ->middleware('can:treasury.close');
    });
    Route::get('/caisse/audit', [JournalCaisseController::class, 'audit'])
        ->name('caisse.audit')
        ->middleware('can:treasury.audit');

    // ==========================================
    // 3. OPÉRATIONS FINANCIÈRES PROTÉGÉES PAR CAISSE OUVERTE
    // ==========================================
    Route::middleware(['cash.open'])->group(function () {
        // Collectes et Mouvements Carnets (Dépôts / Retraits)
        Route::post('/collectes/store', [CollecteController::class, 'store'])
            ->name('collectes.store')
            ->middleware('can:carnets.deposit');
        Route::post('/carnets/depot', [CarnetController::class, 'storeDepot'])
            ->name('carnets.depot')
            ->middleware('can:carnets.deposit');
        Route::post('/carnets/retrait', [CarnetController::class, 'storeRetrait'])
            ->name('carnets.retrait')
            ->middleware('can:carnets.withdraw');
        Route::get('/retraits/{retrait}/recu', [CarnetController::class, 'receipt'])
            ->name('retraits.receipt')
            ->middleware('can:carnets.view');

        // Crédits & Règlements
        Route::patch('credits/{credit}/payments/{payment}', [CreditController::class, 'updatePayment'])
            ->name('credits.payments.update')
            ->middleware('can:credits.payments');
    });
    Route::post('credits/{credit}/approve', [CreditController::class, 'approve'])
        ->name('credits.approve')
        ->middleware(['can:credits.approve', 'cash.open']);
    Route::post('prets/{id}/valider', [PretInstructionController::class, 'valider'])
        ->name('prets.valider')
        ->middleware(['can:credits.approve', 'cash.open']);
    Route::get('/collectes/{collecte}/recu', [CollecteController::class, 'receipt'])
        ->name('collectes.receipt')
        ->middleware('can:carnets.view');
    Route::get('/depots/{depot}/recu', [CarnetController::class, 'depotReceipt'])
        ->name('depots.receipt')
        ->middleware('can:carnets.view');
    Route::post('credits/{credit}/settle-with-savings', [CreditController::class, 'settleCreditWithSavings'])
        ->name('credits.settle-with-savings')
        ->middleware('can:credits.settle');
    Route::get('credits/{credit}/settlements/{settlement}/receipt', [CreditController::class, 'settlementReceipt'])
        ->name('credits.settlements.receipt')
        ->middleware('can:credits.view');
    Route::post('prets/{id}/decaisser', [PretInstructionController::class, 'decaisser'])
        ->name('prets.decaisser')
        ->middleware(['permission.feedback:credits.disburse', 'can:credits.disburse', 'cash.open']);

    // ==========================================
    // 4. SYNCHRONISATIONS & CYCLES
    // ==========================================
    Route::middleware(['can:sync.view'])->group(function () {
        Route::get('/sync-batches', [SyncBatchController::class, 'index'])->name('sync-batches.index');
        Route::get('/sync-batches/{syncBatch}', [SyncBatchController::class, 'show'])->name('sync-batches.show');
        Route::post('/sync-batches/{syncBatch}/approve', [SyncBatchController::class, 'approve'])
            ->name('sync-batches.approve')
            ->middleware('can:sync.validate');
        Route::post('/sync-batches/{syncBatch}/reject', [SyncBatchController::class, 'reject'])
            ->name('sync-batches.reject')
            ->middleware('can:sync.validate');
    });

    Route::get('/cycles', [AdminCycleController::class, 'index'])->name('cycles.index')->middleware('can:cycles.view');
    Route::post('/cycles/store', [AdminCycleController::class, 'store'])->name('cycles.store')->middleware('can:cycles.manage');
    Route::patch('/cycles/{cycle}/mark-withdrawn', [AdminCycleController::class, 'markWithdrawn'])
        ->name('cycles.mark-withdrawn')
        ->middleware('can:cycles.manage');
    Route::get('/performance-terrain', [AgentPerformanceController::class, 'index'])
        ->name('performance-terrain.index')
        ->middleware('can:performance.view');
    Route::get('/performance-terrain/export', [AgentPerformanceController::class, 'export'])
        ->name('performance-terrain.export')
        ->middleware(['can:performance.view', 'can:performance.export']);

    // ==========================================
    // 5. GESTION DU PERSONNEL & RESSOURCES HUMAINES
    // ==========================================
    // Fonctions & Employés Administratifs
    Route::resource('fonctions', FonctionController::class)->except(['create', 'show', 'edit'])->middleware('can:employees.update');
    Route::resource('employes', EmployeAdministratifController::class)->only(['index', 'show'])->middleware('can:employees.view');
    Route::resource('employes', EmployeAdministratifController::class)->only(['create', 'store'])->middleware('can:employees.create');
    Route::resource('employes', EmployeAdministratifController::class)->only(['edit', 'update'])->middleware('can:employees.update');
    Route::resource('employes', EmployeAdministratifController::class)->only(['destroy'])->middleware('can:employees.delete');
    Route::patch('/employes/{employe}/toggle-status', [EmployeAdministratifController::class, 'toggleStatus'])
        ->name('employes.toggle-status')
        ->middleware('can:employees.update');
    // Agents
    Route::resource('agents', AgentController::class)->only(['create', 'store'])->middleware('can:agents.create');
    Route::resource('agents', AgentController::class)->only(['index', 'show'])->middleware('can:agents.view');
    Route::resource('agents', AgentController::class)->only(['edit', 'update'])->middleware('can:agents.update');
    Route::resource('agents', AgentController::class)->only(['destroy'])->middleware('can:agents.delete');
    Route::patch('agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])->name('agents.toggleStatus')->middleware('can:agents.activate');
    Route::get('agents/export/{format}', [AgentController::class, 'export'])->name('agents.export')->middleware('can:agents.export');
    Route::post('agents/{agent}/bonus', [AgentController::class, 'storeBonus'])->name('agents.storeBonus')->middleware('can:agents.commissions');
    Route::post('agents/{agent}/calculate-commissions', [AgentController::class, 'calculateCommissions'])->name('agents.calculateCommissions')->middleware('can:agents.commissions');
    Route::get('/agents/{id}/chart-data', [AgentController::class, 'getChartData'])->name('agents.chartData')->middleware('can:agents.view');
    Route::patch('/agents/{id}/toggle-sync', [AgentController::class, 'toggleSync'])->name('agents.toggle-sync')->middleware('can:sync.validate');
    Route::get('/agents/sync-status', function () {
        return response()->json(Agent::pluck('can_sync', 'id'));
    })->name('agents.sync-status')->middleware('can:agents.view');
    Route::get('/agents-list/{historyUlid}', [AgentController::class, 'getAgentsExceptCurrent'])->name('agents.list.except')->middleware('can:agents.view');
    Route::post('agents/{agent}/reset-pin', [AgentController::class, 'resetPin'])->name('agents.reset-pin')->middleware('can:agents.reset-pin');
    // Paies et avances : consultation, préparation, décision et paiement sont séparés.
    Route::post('/agents/{agent}/avances', [SalaryAdvanceController::class, 'storeAgent'])->name('agents.avances.store')->middleware('can:advances.create');
    Route::post('/employes/{employe}/avances', [SalaryAdvanceController::class, 'storeEmploye'])->name('employes.avances.store')->middleware('can:advances.create');
    Route::get('/salary-grids', [SalaryGridController::class, 'index'])->name('salary-grids.index')->middleware('can:payroll.view');
    Route::post('/salary-grids', [SalaryGridController::class, 'store'])->name('salary-grids.store')->middleware('can:payroll.prepare');
    Route::put('/salary-grids/{salary_grid}', [SalaryGridController::class, 'update'])->name('salary-grids.update')->middleware('can:payroll.prepare');
    Route::patch('/salary-grids/{salary_grid}/close', [SalaryGridController::class, 'close'])
        ->name('salary-grids.close')
        ->middleware('can:payroll.prepare');
    Route::get('/payrolls/agents', [PayrollController::class, 'index'])->name('payrolls.index')->middleware('can:payroll.view');
    Route::post('/payroll/store', [PayrollController::class, 'store'])->name('payrolls.store')->middleware('can:payroll.prepare');
    Route::get('/payrolls/bulletin/{id}', [PayrollController::class, 'showValidated'])->name('payrolls.validated')->middleware('can:payroll.view');
    Route::patch('/payrolls/{id}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve')->middleware('can:payroll.approve');
    Route::patch('/payrolls/{id}/reject', [PayrollController::class, 'reject'])->name('payrolls.reject')->middleware('can:payroll.reject');
    Route::patch('/payrolls/{id}/pay', [PayrollController::class, 'pay'])->name('payrolls.pay')->middleware('can:payroll.pay');
    Route::put('/payrolls/avances/{avance}/tranches', [PayrollController::class, 'updateTranches'])->name('payrolls.avances.updateTranches')->middleware('can:payroll.prepare');
    Route::get('/payrolls/preview-details', [PayrollController::class, 'previewDetails'])->name('payrolls.details')->middleware('can:payroll.view');
    Route::get('/payrolls/employes', [PayrollEmployerController::class, 'index'])->name('payrolls.employes.index')->middleware('can:payroll.view');
    Route::get('payrolls/employes/{ulid}', [PayrollEmployerController::class, 'previewDetails'])->name('payrolls.employes.details')->middleware('can:payroll.view');
    Route::post('/payrolls/employes/store', [PayrollEmployerController::class, 'store'])->name('payrolls.employes.store')->middleware('can:payroll.prepare');
    Route::get('/payrolls/bulletin/{id}/employe', [PayrollEmployerController::class, 'showValidated'])->name('payrolls.employe.validated')->middleware('can:payroll.view');
    Route::patch('/payrolls/employes/{ulid}/approve', [PayrollEmployerController::class, 'approve'])->name('payrolls.employes.approve')->middleware('can:payroll.approve');
    Route::patch('/payrolls/employes/{ulid}/reject', [PayrollEmployerController::class, 'reject'])->name('payrolls.employes.reject')->middleware('can:payroll.reject');
    Route::patch('/payrolls/employes/{ulid}/pay', [PayrollEmployerController::class, 'pay'])->name('payrolls.employes.pay')->middleware('can:payroll.pay');
    Route::put('/payrolls/avances/employe/{avance}/tranches', [PayrollEmployerController::class, 'updateTranches'])->name('payrolls.avances.employe.updateTranches')->middleware('can:payroll.prepare');
    Route::post('/payrolls/employes/{ulid}/payroll/store-brouillon', [PayrollEmployerController::class, 'storeBrouillon'])->name('payrolls.employe.store-brouillon')->middleware('can:payroll.prepare');
    Route::get('/payrolls/avances', [SalaryAdvanceController::class, 'index'])->name('payrolls.avance')->middleware('can:advances.view');
    Route::get('/avances/{id}/edit', [SalaryAdvanceController::class, 'edit'])->name('avances.edit')->middleware('can:advances.update');
    Route::put('/avances/{uid}', [SalaryAdvanceController::class, 'update'])->name('avances.update')->middleware('can:advances.update');
    Route::delete('/avances/{id}', [SalaryAdvanceController::class, 'destroy'])->name('avances.destroy')->middleware('can:advances.delete');
    Route::patch('/payrolls/avance/{id}/valider', [SalaryAdvanceController::class, 'validerAvance'])->name('payrolls.avance.valider')->middleware('can:advances.approve');
    Route::patch('/payrolls/avance/{id}/rejeter', [SalaryAdvanceController::class, 'rejeterAvance'])->name('payrolls.avance.rejeter')->middleware('can:advances.reject');
    Route::patch('/payrolls/avance/{id}/decaisser', [SalaryAdvanceController::class, 'decaisserAvance'])->name('payrolls.avance.decaisser')->middleware('can:advances.disburse');

    // ==========================================
    // 6. CLIENTS, CARNETS & CRÉDITS (Général)
    // ==========================================
    Route::resource('clients', ClientController::class)->only(['create', 'store'])->middleware('can:clients.create');
    Route::resource('clients', ClientController::class)->only(['index', 'show'])->middleware('can:clients.view');
    Route::resource('clients', ClientController::class)->only(['edit', 'update'])->middleware('can:clients.update');
    Route::resource('clients', ClientController::class)->only(['destroy'])->middleware('can:clients.delete');
    Route::get('clients/export/{format}', [ClientController::class, 'export'])->name('clients.export')->middleware('can:clients.export');
    Route::get('clients/{client}/export-history', [ClientController::class, 'exportHistory'])->name('clients.exportHistory')->middleware('can:clients.export');
    Route::post('/client-carnets', [ClientController::class, 'storeNumCarnet'])->name('client-carnets.store')->middleware('can:carnets.create');

    Route::resource('credits', CreditController::class)->only(['create', 'store'])->middleware('can:credits.create');
    Route::resource('credits', CreditController::class)->only(['index', 'show'])->middleware('can:credits.view');
    Route::get('/credit-settings', [CreditConfigurationController::class, 'index'])->name('credit-settings.index')->middleware('can:credits.configure');
    Route::post('/credit-settings/types', [CreditConfigurationController::class, 'storeType'])->name('credit-settings.types.store')->middleware('can:credits.configure');
    Route::put('/credit-settings/types/{creditType}', [CreditConfigurationController::class, 'updateType'])->name('credit-settings.types.update')->middleware('can:credits.configure');
    Route::post('/credit-settings/products', [CreditConfigurationController::class, 'storeProduct'])->name('credit-settings.products.store')->middleware('can:credits.configure');
    Route::put('/credit-settings/products/{creditProduct}', [CreditConfigurationController::class, 'updateProduct'])->name('credit-settings.products.update')->middleware('can:credits.configure');
    Route::post('/credit-settings/objects', [CreditConfigurationController::class, 'storeObject'])->name('credit-settings.objects.store')->middleware('can:credits.configure');
    Route::put('/credit-settings/objects/{creditObject}', [CreditConfigurationController::class, 'updateObject'])->name('credit-settings.objects.update')->middleware('can:credits.configure');
    Route::get('/credits/check-pending/{carnetId}', [CreditController::class, 'checkPending'])->name('credits.check-pending')->middleware('can:credits.view');

    // Carnets & API associées
    Route::get('/carnets/get-tontines/{clientId}', [CarnetController::class, 'getTontinesByClient'])->name('carnets.get-tontines')->middleware('can:carnets.view');
    Route::get('/carnets/get-available-numbers/{clientId}', [CarnetController::class, 'getAvailableCarnetNumbers'])->name('carnets.get-available-numbers')->middleware('can:carnets.view');
    Route::get('/carnets/get-by-client/{clientId}', [CarnetController::class, 'getCarnetsByClient'])->name('carnets.get-by-client')->middleware('can:carnets.view');
    Route::get('/carnets/get-by-client-credit/{clientId}', [CreditController::class, 'getCarnetsByClient'])->name('carnets.get-by-client-credit')->middleware('can:credits.view');
    Route::get('/carnets/details/{id}', [CreditController::class, 'getCarnetDetails'])->name('carnets.details')->middleware('can:credits.view');
    Route::get('/carnets/{type?}', [CarnetController::class, 'index'])->name('carnets.index')->middleware('can:carnets.view')->where('type', 'tontine|compte');
    Route::get('/carnets/{carnet}', [CarnetController::class, 'show'])->name('carnets.show')->middleware('can:carnets.view');
    Route::post('/carnets/store', [CarnetController::class, 'store'])->name('carnets.store')->middleware(['can:carnets.create', 'cash.open']);
    Route::put('/carnets/{carnet}', [CarnetController::class, 'update'])->name('carnets.update')->middleware('can:carnets.update');
    Route::delete('/carnets/{carnet}', [CarnetController::class, 'destroy'])->name('carnets.destroy')->middleware('can:carnets.delete');
    Route::post('/carnet-historique/{ulid}/reassign', [CarnetController::class, 'reassign'])->name('carnets.reassign')->middleware('can:carnets.update');

    // Prêts (Affichage / Index)
    Route::get('prets', [PretInstructionController::class, 'index'])->name('prets.index')->middleware('can:credits.view');
    Route::get('prets/{id}', [PretInstructionController::class, 'show'])->name('prets.show')->middleware('can:credits.view');

    // ==========================================
    // 7. COMPTABILITÉ, CHARGES, DÉPENSES & RECETTES
    // ==========================================
    Route::get('/stocks', [StockCarnetController::class, 'index'])->name('stocks.index')->middleware('can:carnets.stock');
    Route::post('/stocks/entree', [StockCarnetController::class, 'storeEntree'])->name('stocks.entree')->middleware('can:carnets.stock');

    Route::prefix('charges')->name('charges.')->group(function () {
        Route::get('/', [ChargeController::class, 'index'])->name('index')->middleware('can:charges.view');
        Route::get('/categories/create', [ChargeController::class, 'createCategory'])->name('categories.create')->middleware('can:charges.manage');
        Route::post('/categories', [ChargeController::class, 'storeCategory'])->name('categories.store')->middleware('can:charges.manage');
        Route::patch('/categories/{ulid}/toggle', [ChargeController::class, 'toggleCategoryStatus'])->name('categories.toggle')->middleware('can:charges.manage');
        Route::post('/types', [ChargeController::class, 'storeType'])->name('types.store')->middleware('can:charges.manage');
    });

    Route::resource('depenses', DepenseController::class)->only(['index', 'show'])->parameters(['depenses' => 'depense:ulid'])->middleware('can:expenses.view');
    Route::resource('depenses', DepenseController::class)->only(['create', 'store'])->parameters(['depenses' => 'depense:ulid'])->middleware('can:expenses.create');
    Route::resource('depenses', DepenseController::class)->only(['edit', 'update'])->parameters(['depenses' => 'depense:ulid'])->middleware('can:expenses.update');
    Route::resource('depenses', DepenseController::class)->only(['destroy'])->parameters(['depenses' => 'depense:ulid'])->middleware('can:expenses.delete');
    Route::get('/recettes', [RecetteController::class, 'index'])->name('recettes.index')->middleware('can:revenues.view');

    // ==========================================
    // 8. COMMISSIONS, BONUS & PARAMÈTRES
    // ==========================================
    Route::resource('bonuses', BonusController::class)->only(['index'])->middleware('can:commissions.view');
    Route::resource('bonuses', BonusController::class)->only(['store', 'destroy'])->middleware('can:commissions.manage');
    Route::post('/bonuses/store', [BonusController::class, 'store'])->name('admin.bonuses.store')->middleware('can:commissions.manage');
    Route::post('bonuses/bulk-approve', [BonusController::class, 'bulkApprove'])->name('bonuses.bulk-approve')->middleware('can:commissions.manage');
    Route::post('bonuses/{id}/approve-single', [BonusController::class, 'approveSingle'])->name('bonuses.approve-single')->middleware('can:commissions.manage');
    Route::get('paiements/historique', [BonusController::class, 'history'])->name('bonuses.history')->middleware('can:commissions.view');

    Route::put('/parametres-tarifs', [CategoryTontineController::class, 'updateTarifEpargne'])->name('parametres-tarifs.update')->middleware('can:carnets.update');
    Route::resource('categories', CategoryTontineController::class)->middleware('can:carnets.update');

    // ==========================================
    // 9. UTILISATEURS, RÔLES & API STATS GRAPHIQUES
    // ==========================================
    Route::resource('roles', RoleController::class)->only(['index', 'show'])->middleware('can:roles.view');
    Route::resource('roles', RoleController::class)->only(['create', 'store'])->middleware('can:roles.create');
    Route::resource('roles', RoleController::class)->only(['edit', 'update'])->middleware('can:roles.update');
    Route::resource('roles', RoleController::class)->only(['destroy'])->middleware('can:roles.delete');
    Route::resource('users', UserController::class)->only(['index', 'show'])->middleware('can:users.view');
    Route::resource('users', UserController::class)->only(['create', 'store'])->middleware('can:users.create');
    Route::resource('users', UserController::class)->only(['edit', 'update'])->middleware('can:users.update');
    Route::resource('users', UserController::class)->only(['destroy'])->middleware('can:users.delete');

    Route::get('/api/evolution-ventes', [CarnetController::class, 'evolutionVentes'])->name('evolution-ventes')->middleware('can:carnets.view');
    Route::get('/api/repartition-tontines', [CarnetController::class, 'repartitionTontines'])->name('repartition-tontines')->middleware('can:carnets.view');

});

Route::get('/api/sync-batches/partial', function (Request $request) {
    $since     = $request->query('since');
    $sinceDate = \Carbon\Carbon::parse($since);

    $batches = \App\Models\SyncBatch::where('created_at', '>', $sinceDate)->latest()->get();

    if ($batches->isEmpty()) {
        return response()->json(['html' => '']);
    }

    return response()->json([
        'html'       => view('admin.partials.sync-table-rows', compact('batches'))->render(),
        'serverTime' => now()->toDateTimeString(),
    ]);
})->middleware(['auth', 'role:Admin', 'can:sync.view']);

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
    Route::get('/gains', function () {
        return view('pwa/gains');
    })->name('gains');
    Route::get('/stats', function () {
        return view('pwa/stats');
    })->name('stats');
    Route::get('/nouveau-carnet/{client_id}', [CarnetController::class, 'agentCreate'])->name('carnets.create');
    Route::post('/store-carnet', [CarnetController::class, 'store'])->name('carnets.store');
    Route::get('/security-pin', [PwaController::class, 'showSecurityPin'])->name('pin');
    Route::get('/check-status/{matricule}', [PwaController::class, 'checkAgentStatus']);

});
Route::middleware('throttle:sync')->prefix('pwa')->name('pwa.')->group(function () {
    Route::post('/lock-sync', [PwaController::class, 'lockSync'])->name('lock-sync');
    Route::post('/sync-data-post', [SyncController::class, 'store'])->name('sync-data-post');
    Route::get('/sync-batches/{syncUuid}/status', [SyncController::class, 'batchStatus'])->name('sync-batches.status');
    Route::post('/sync-batches/{syncUuid}/cancel', [SyncController::class, 'cancelBatch'])->name('sync-batches.cancel');
});

// Route::middleware('throttle:sync')->group(function () {
//     Route::post('/lock-sync', [PwaController::class, 'lockSync'])->name('lock-sync');
//     Route::post('/sync-data-post', [SyncController::class, 'store'])->name('sync-data-post');
//     Route::get('/sync-batches/{syncUuid}/status', [SyncController::class, 'batchStatus'])->name('sync-batches.status');
//     Route::post('/sync-batches/{syncUuid}/cancel', [SyncController::class, 'cancelBatch'])->name('sync-batches.cancel');
// });
Route::get('/api/ping', function () {
    return response()->json(['status' => 'ok']);
});
Route::middleware(['auth:sanctum', 'role:Agent', 'throttle:sync'])->post('/sync', [SyncController::class, 'synchroniser']);
