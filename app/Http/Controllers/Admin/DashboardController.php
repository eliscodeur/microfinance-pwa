<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use App\Models\SyncBatch;
use App\Models\Credit;
use App\Models\Collecte; // Assure-toi que ce modèle existe
use Illuminate\Http\Request;

class DashboardController extends Controller
{
public function index()
{
    // --- STATISTIQUES DE BASE ---
    $totalAgents = Agent::count();
    $totalClients = Client::count();
    $pendingSyncBatches = SyncBatch::where('status', 'pending_review')->count();

    // --- LOGIQUE DE TRÉSORERIE ---
    // 1. Collecte Brute : Total historique de l'argent encaissé
    $totalCollecteBrute = Collecte::sum('montant');
    
    // 2. Retraits : Total des sommes rendues aux clients (Assurez-vous d'avoir ce modèle)
    // $totalRetraits = Retrait::sum('montant'); 
    $totalRetraits = 0; // À remplacer par votre calcul réel

    // 3. Trésorerie Nette : Ce qui reste physiquement dans le coffre
    $tresorerieNette = $totalCollecteBrute - $totalRetraits;

    // --- STATISTIQUES CRÉDITS & PÉNALITÉS ---
    // Encours Total : Ce que les clients doivent encore rembourser
    $totalEncours = Credit::where('statut', 'active')->get()->sum(function($c) {
        return ($c->montant_accorde + $c->interet_total) - $c->montant_rembourse;
    });

    $countClientsEnRetard = Credit::where('penalty_amount', '>', 0)
        ->where('statut', 'active')
        ->count();

    $totalPenalites = Credit::where('statut', 'active')->sum('penalty_amount');

    $topPenalites = Credit::with(['client', 'carnet', 'agent'])
        ->where('penalty_amount', '>', 0)
        ->where('statut', 'active')
        ->orderByDesc('penalty_amount')
        ->take(5)
        ->get();

    // --- DONNÉES DU GRAPHIQUE (7 DERNIERS JOURS) ---
    $dates = [];
    $collectesData = [];
    $remboursementsData = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $dates[] = $date->format('d M');
        
        $collectesData[] = Collecte::whereDate('created_at', $date->format('Y-m-d'))->sum('montant');
        $remboursementsData[] = Credit::whereDate('updated_at', $date->format('Y-m-d'))
            ->where('statut', 'active')
            ->sum('montant_rembourse');
    }

    // --- DONNÉES DU GRAPHIQUE (12 DERNIERS MOIS) ---
    $monthsLabels = [];
    $monthsCollecte = [];
    $monthsRemboursement = [];

    for ($i = 11; $i >= 0; $i--) {
        $monthDate = now()->subMonths($i);
        $monthsLabels[] = $monthDate->translatedFormat('M Y');
        
        $monthsCollecte[] = Collecte::whereYear('created_at', $monthDate->year)
            ->whereMonth('created_at', $monthDate->month)
            ->sum('montant');
            
        $monthsRemboursement[] = Credit::whereYear('updated_at', $monthDate->year)
            ->whereMonth('updated_at', $monthDate->month)
            ->where('statut', 'active')
            ->sum('montant_rembourse');
    }

    return view('admin.dashboard', compact(
        'totalAgents', 'totalClients', 'totalCollecteBrute', 'totalRetraits', 
        'tresorerieNette', 'pendingSyncBatches', 'totalEncours', 
        'countClientsEnRetard', 'totalPenalites', 'topPenalites',
        'dates', 'collectesData', 'remboursementsData',
        'monthsLabels', 'monthsCollecte', 'monthsRemboursement'
    ));
}
    public function getChartData(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        // On récupère toutes les dates de la période
        $period = \Carbon\CarbonPeriod::create($start, $end);
        $labels = [];
        $collecte = [];
        $remboursement = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $labels[] = $date->format('d M');
            
            $collecte[] = \App\Models\Collecte::whereDate('created_at', $formattedDate)->sum('montant');
            $remboursement[] = \App\Models\Credit::whereDate('updated_at', $formattedDate)
                ->where('statut', 'active')
                ->sum('montant_rembourse');
        }

        return response()->json([
            'labels' => $labels,
            'collecte' => $collecte,
            'remboursement' => $remboursement
        ]);
    }
}