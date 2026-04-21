<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $cycles = Cycle::with(['agent', 'carnet.client', 'retrait.admin'])
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
            'all' => Cycle::count(),
            'active' => Cycle::where('statut', 'en_cours')->count(),
            'awaiting_withdrawal' => Cycle::where('statut', 'termine')->whereNull('retire_at')->count(),
            'withdrawn' => Cycle::whereNotNull('retire_at')->count(),
        ];

        return view('admin.cycles.index', compact('cycles', 'filter', 'totals'));
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
            $commission = (float) ($cycle->montant_journalier ?? 0);
            $montantNet = max(0, $montantTotal - $commission);

            Retrait::create([
                'cycle_id' => $cycle->id,
                'client_id' => $cycle->client_id,
                'carnet_id' => $cycle->carnet_id,
                'admin_id' => auth()->id(),
                'montant_total' => $montantTotal,
                'commission' => $commission,
                'montant_net' => $montantNet,
                'date_retrait' => $withdrawDate,
                'note' => $request->input('note'),
            ]);

            $cycle->update([
                'retire_at' => $withdrawDate,
            ]);
        });

        return back()->with('success', 'Le retrait du cycle a ete enregistre avec succes.');
    }
}
