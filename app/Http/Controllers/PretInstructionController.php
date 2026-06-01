<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Credit;
use App\Services\CreditCalculator;
use Carbon\Carbon;

class PretInstructionController extends Controller
{
    /**
     * Liste les crédits triés par onglets pour Inertia
     */
    public function index()
    {
        $credits = Credit::with('client')->orderBy('created_at', 'desc')->get();

        $creditsNonApprouves = $credits->filter(function ($c) {
            return in_array($c->statut, ['pending', 'soumis', 'en_etude']);
        })->values();

        $creditsApprouves = $credits->filter(function ($c) {
            return $c->statut === 'approved' || $c->statut === 'approuve';
        })->values();

        $historique = $credits->filter(function ($c) {
            return in_array($c->statut, ['rejected', 'rejete', 'active', 'solder', 'solde']);
        })->values();

        return Inertia::render('Prets/Index', [
            'creditsNonApprouves' => $creditsNonApprouves,
            'creditsApprouves' => $creditsApprouves,
            'historique' => $historique,
        ]);
    }

    /**
     * Affiche la fiche d'instruction d'un crédit avec diagnostic épargne
     */
    public function show($id)
    {
        $credit = Credit::with(['client.carnets.cycles.collectes'])->findOrFail($id);
        $client = $credit->client;

        $carnets = $client->carnets ?? collect();
        $nombreCarnets = $carnets->count();

        // Calculs tolérants : certains projets nomment le solde différemment
        $totalEpargne = $carnets->sum(function ($c) {
            return $c->solde ?? ($c->balance ?? 0);
        });

        $cycles = $carnets->map->cycles->flatten();
        $cyclesCompletes = $cycles->filter(function ($cycle) {
            return isset($cycle->statut) ? $cycle->statut === 'termine' : false;
        })->count();

        $totalCollectes = $cycles->flatMap->collectes->sum('montant');

        $regularite = null;
        $totalCycles = $cycles->count();
        if ($totalCycles > 0) {
            $regularite = round(($cyclesCompletes / $totalCycles) * 100, 0);
        }

        $diagnostic = [
            'nombreCarnets' => $nombreCarnets,
            'totalEpargne' => $totalEpargne,
            'cyclesCompletes' => $cyclesCompletes,
            'totalCollectes' => $totalCollectes,
            'regularitePourcent' => $regularite,
        ];

        return Inertia::render('Prets/Show', [
            'credit' => $credit,
            'client' => $client,
            'diagnostic' => $diagnostic,
        ]);
    }

    /**
     * Valide une action du comité : en_etude, approuve, rejete
     */
    public function valider(Request $request, $id)
    {
        $credit = Credit::findOrFail($id);

        $rules = ['action' => 'required|in:en_etude,approuve,rejete'];
        if ($request->input('action') === 'approuve') {
            $rules['montant_accorde'] = 'required|numeric|min:0';
            $rules['taux'] = 'required|numeric|min:0';
            $rules['date_debut'] = 'required|date';
        }
        if ($request->input('action') === 'rejete') {
            $rules['motif'] = 'required|string';
        }

        $data = $request->validate($rules);

        if ($data['action'] === 'en_etude') {
            $credit->statut = 'pending';
        } elseif ($data['action'] === 'approuve') {
            $credit->statut = 'approved';
            $credit->montant_accorde = $data['montant_accorde'];
            $credit->taux = $data['taux'];
            $credit->date_debut = $data['date_debut'];
            $credit->approved_at = Carbon::now();
        } else {
            $credit->statut = 'rejected';
            $metadata = $credit->metadata ?? [];
            $metadata['rejection_reason'] = $data['motif'] ?? null;
            $credit->metadata = $metadata;
        }

        $credit->save();

        return redirect()->back()->with('success', 'Statut mis à jour.');
    }

    /**
     * Effectue le décaissement : change l'état et génère l'échéancier
     */
    public function decaisser($id)
    {
        $credit = Credit::with('payments')->findOrFail($id);
        if ($credit->statut !== 'approved') {
            return redirect()->back()->with('error', 'Le crédit doit être approuvé avant décaissement.');
        }

        $scheduleData = [
            'montant_demande' => $credit->montant_accorde ?? $credit->montant_demande,
            'taux' => $credit->taux,
            'taux_manuelle' => $credit->taux_manuelle,
            'mode' => $credit->mode,
            'periodicite' => $credit->periodicite,
            'nombre_echeances' => $credit->nombre_echeances,
            'date_debut' => $credit->date_debut ?? now()->toDateString(),
        ];

        $schedule = CreditCalculator::buildSchedule($scheduleData);
        $interestTotal = CreditCalculator::totalInterest($schedule);
        $monthlyAmount = collect($schedule)->avg('total');
        $dateFin = collect($schedule)->last()['date'] ?? $scheduleData['date_debut'];

        $credit->montant_echeance = round($monthlyAmount, 2);
        $credit->interet_total = round($interestTotal, 2);
        $credit->date_fin_prevue = $dateFin;
        $credit->statut = 'active';
        $credit->save();

        $credit->payments()->delete();
        foreach ($schedule as $item) {
            $credit->payments()->create([
                'echeance' => $item['numero'],
                'due_date' => $item['date'],
                'montant_principal' => $item['principal'],
                'montant_interets' => $item['interest'],
                'montant_total' => $item['total'],
                'status' => 'pending',
            ]);
        }

        return redirect()->route('admin.prets.show', $credit->id)->with('success', 'Crédit décaisse et phase de remboursement activée.');
    }
}
