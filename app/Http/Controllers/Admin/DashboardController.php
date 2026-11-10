<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\Credit;
use App\Models\MouvementCaisse;
use App\Models\SyncBatch;
use App\Services\CashClosingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $canViewTreasury = auth()->user()->can('treasury.view');
        $canViewCreditPortfolio = auth()->user()->can('credits.view');
        $canViewAgents = auth()->user()->can('agents.view');
        $canViewClients = auth()->user()->can('clients.view');
        $canViewSync = auth()->user()->can('sync.view');

        // --- STATISTIQUES DE BASE ---
        $totalAgents = $canViewAgents ? Agent::count() : 0;
        $totalClients = $canViewClients ? Client::count() : 0;
        $pendingSyncBatches = $canViewSync
            ? SyncBatch::where('status', 'pending_review')->count()
            : 0;

        $tresorerieJour = null;
        $soldeEspecesCaissesOuvertes = null;
        $dates = [];
        $entreesData = [];
        $sortiesData = [];
        $monthsLabels = [];
        $monthsEntrees = [];
        $monthsSorties = [];
        $thisMonthLabels = [];
        $thisMonthEntrees = [];
        $thisMonthSorties = [];
        $tresorerieSeptJours = [];

        if ($canViewTreasury) {
            $dateDebutHistorique = now()->copy()->subMonths(11)->startOfMonth();
            $mouvementsParJour = $this->mouvementsParJour($dateDebutHistorique, now()->endOfDay());

            $mouvementDuJour = $mouvementsParJour->get(now()->toDateString());
            $entreesDuJour = (float) ($mouvementDuJour->entrees ?? 0);
            $sortiesDuJour = (float) ($mouvementDuJour->sorties ?? 0);
            $tresorerieJour = [
                'entrees' => $entreesDuJour,
                'sorties' => $sortiesDuJour,
                'flux_net' => $entreesDuJour - $sortiesDuJour,
            ];

            $closingService = app(CashClosingService::class);
            $soldeEspecesCaissesOuvertes = CashSession::query()
                ->where('statut', 'ouverte')
                ->get()
                ->sum(function (CashSession $session) use ($closingService) {
                    return (float) $closingService->calculateSummary($session)['solde_theorique'];
                });

            for ($i = 6; $i >= 0; $i--) {
                $date = now()->copy()->subDays($i);
                $jour = $mouvementsParJour->get($date->toDateString());
                $entrees = (float) ($jour->entrees ?? 0);
                $sorties = (float) ($jour->sorties ?? 0);

                $dates[] = $date->format('d M');
                $entreesData[] = $entrees;
                $sortiesData[] = $sorties;
                $tresorerieSeptJours[] = [
                    'date' => $date->format('d/m/Y'),
                    'entrees' => $entrees,
                    'sorties' => $sorties,
                    'flux_net' => $entrees - $sorties,
                ];
            }

            $dateDebutMois = now()->copy()->startOfMonth();
            for ($date = $dateDebutMois->copy(); $date->lte(now()); $date->addDay()) {
                $jour = $mouvementsParJour->get($date->toDateString());
                $thisMonthLabels[] = $date->format('d/m');
                $thisMonthEntrees[] = (float) ($jour->entrees ?? 0);
                $thisMonthSorties[] = (float) ($jour->sorties ?? 0);
            }

            for ($i = 11; $i >= 0; $i--) {
                $monthDate = now()->copy()->subMonths($i);
                $monthPrefix = $monthDate->format('Y-m-');
                $monthRows = $mouvementsParJour->filter(function ($movement, $day) use ($monthPrefix) {
                    return str_starts_with($day, $monthPrefix);
                });

                $monthsLabels[] = $monthDate->translatedFormat('M Y');
                $monthsEntrees[] = (float) $monthRows->sum('entrees');
                $monthsSorties[] = (float) $monthRows->sum('sorties');
            }
        }

        $totalEncours = 0;
        $countClientsEnRetard = 0;
        $totalPenalites = 0;
        $topPenalites = collect();

        if ($canViewCreditPortfolio) {
            $totalEncours = Credit::where('statut', 'active')->get()->sum(function ($c) {
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
        }

        return view('admin.dashboard', compact(
            'totalAgents', 'totalClients', 'pendingSyncBatches', 'totalEncours',
            'countClientsEnRetard', 'totalPenalites', 'topPenalites',
            'tresorerieJour', 'soldeEspecesCaissesOuvertes',
            'dates', 'entreesData', 'sortiesData', 'tresorerieSeptJours',
            'monthsLabels', 'monthsEntrees', 'monthsSorties',
            'thisMonthLabels', 'thisMonthEntrees', 'thisMonthSorties'
        ));
    }

    public function getChartData(Request $request)
    {
        $validated = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);
        $start = Carbon::createFromFormat('!Y-m-d', $validated['start']);
        $end = Carbon::createFromFormat('!Y-m-d', $validated['end']);

        if ($start->diffInDays($end) > 366) {
            throw ValidationException::withMessages([
                'end' => 'La période personnalisée ne peut pas dépasser 12 mois.',
            ]);
        }

        $mouvementsParJour = $this->mouvementsParJour($start, $end->copy()->endOfDay());

        $labels = [];
        $entrees = [];
        $sorties = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $mouvement = $mouvementsParJour->get($date->toDateString());
            $labels[] = $date->format('d M');
            $entrees[] = (float) ($mouvement->entrees ?? 0);
            $sorties[] = (float) ($mouvement->sorties ?? 0);
        }

        return response()->json([
            'labels' => $labels,
            'entrees' => $entrees,
            'sorties' => $sorties,
        ]);
    }

    private function mouvementsParJour(Carbon $start, Carbon $end)
    {
        return MouvementCaisse::query()
            ->whereBetween('date_mouvement', [$start, $end])
            ->selectRaw(
                'DATE(date_mouvement) as jour, ' .
                'SUM(CASE WHEN sens = ? THEN montant ELSE 0 END) as entrees, ' .
                'SUM(CASE WHEN sens = ? THEN montant ELSE 0 END) as sorties',
                ['entree', 'sortie']
            )
            ->groupBy('jour')
            ->orderBy('jour')
            ->get()
            ->keyBy('jour');
    }
}
