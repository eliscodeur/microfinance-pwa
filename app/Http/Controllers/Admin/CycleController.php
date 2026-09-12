<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carnet;
use App\Models\Cycle;
use App\Models\Retrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CycleController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $cycles = Cycle::with(['agent', 'carnet.client', 'retraits.admin'])
            ->withSum('collectes', 'montant')
            ->when($filter === 'active', function ($query) {
                $query->where('statut', 'en_cours');
            })
            ->when($filter === 'awaiting_withdrawal', function ($query) {
                $query->where('statut', 'termine')->whereNull('retire_at');
            })
            ->when($filter === 'withdrawn', function ($query) {
                $query->whereNotNull('retire_at');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'all'                 => Cycle::count(),
            'active'              => Cycle::where('statut', 'en_cours')->count(),
            'awaiting_withdrawal' => Cycle::where('statut', 'termine')->whereNull('retire_at')->count(),
            'withdrawn'           => Cycle::whereNotNull('retire_at')->count(),
        ];

        return view('admin.cycles.index', compact('cycles', 'filter', 'totals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'carnet_id'          => 'required|exists:carnets,id',
            'montant_journalier' => 'required|numeric|min:1',
        ]);

        // Vérifier s'il n'y a pas déjà un cycle en cours pour ce carnet
        $cycleActif = Cycle::where('carnet_id', $request->carnet_id)
            ->where('statut', 'en_cours')
            ->first();

        if ($cycleActif) {
            return back()->with('error', 'Un cycle est déjà en cours pour ce carnet.');
        }

        // Récupérer le carnet pour obtenir le client_id et agent_id associés
        $carnet = Carnet::with('client')->findOrFail($request->carnet_id);

        // Paramètres du cycle (31 jours ouvrés / hors weekends)
        $nombreJours    = 31;
        $dateDebut      = now();
        $dateFinPrévue = $dateDebut->copy();

        // Ajouter 31 jours en sautant les samedis (6) et dimanches (0)
        $joursAjoutes = 0;
        while ($joursAjoutes < $nombreJours) {
            $dateFinPrévue->addDay();
            // Si ce n'est ni un samedi (6) ni un dimanche (0), on incrémente
            if (! in_array($dateFinPrévue->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $joursAjoutes++;
            }
        }

        // Création complète du cycle avec toutes les liaisons requises
        Cycle::create([
            'cycle_uid'             => \Illuminate\Support\Str::uuid(),
            'carnet_id'             => $carnet->id,
            'client_id'             => $carnet->client_id,
            'agent_id'              => $carnet->agent_id,
            'user_id'               => auth()->id(),
            'montant_journalier'    => $request->montant_journalier,
            'nombre_jours_objectif' => $nombreJours,
            'statut'                => 'en_cours',
            'date_debut'            => $dateDebut,
            'date_fin_prevue'       => $dateFinPrévue,
        ]);

        return back()->with('swal_success', 'Cycle ouvert avec succès !');
    }

    public function markWithdrawn(Cycle $cycle, Request $request)
    {
        if ($cycle->statut !== 'termine') {
            return back()->with('error', 'Seuls les cycles termines peuvent etre marques comme retires.');
        }

        if ($cycle->retire_at) {
            return back()->with('error', 'Ce cycle a deja ete marque comme retire.');
        }

        $withdrawDate = $request->input('retire_at')
            ? Carbon::parse($request->input('retire_at'))
            : now();

        DB::transaction(function () use ($cycle, $withdrawDate, $request) {
            $cycle->loadMissing(['collectes', 'retrait']);

            $montantTotal = (float) $cycle->collectes->sum('montant');
            $commission   = (float) ($cycle->montant_journalier ?? 0);
            $montantNet   = max(0, $montantTotal - $commission);

            Retrait::create([
                'cycle_id'      => $cycle->id,
                'client_id'     => $cycle->client_id,
                'carnet_id'     => $cycle->carnet_id,
                'admin_id'      => auth()->id(),
                'montant_total' => $montantTotal,
                'commission'    => $commission,
                'montant_net'   => $montantNet,
                'date_retrait'  => $withdrawDate,
                'note'          => $request->input('note'),
            ]);

            $cycle->update([
                'retire_at' => $withdrawDate,
            ]);
        });

        return back()->with('success', 'Le retrait du cycle a ete enregistre avec succes.');
    }
}
