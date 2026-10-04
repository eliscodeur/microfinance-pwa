<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MouvementCaisse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JournalCaisseController extends Controller
{
    public function index(Request $request)
    {
        $query = MouvementCaisse::with('user');

        // Gestion de la période (Par défaut : Aujourd'hui pour un journal de caisse)
        if ($request->filled('periode')) {
            $periodeStr = urldecode(str_replace('+', ' ', $request->periode));
            $dates      = preg_split('/\s+au\s+/i', trim($periodeStr));

            if (count($dates) == 2) {
                $dateDebut = trim($dates[0]);
                $dateFin   = trim($dates[1]);
                $query->whereDate('date_mouvement', '>=', $dateDebut)
                    ->whereDate('date_mouvement', '<=', $dateFin);
                $periodeLibelle = "Du " . Carbon::parse($dateDebut)->format('d/m/Y') . " au " . Carbon::parse($dateFin)->format('d/m/Y');
            } elseif (count($dates) == 1 && ! empty($dates[0])) {
                $dateUnique = trim($dates[0]);
                $query->whereDate('date_mouvement', $dateUnique);
                $periodeLibelle = "Le " . Carbon::parse($dateUnique)->format('d/m/Y');
            } else {
                $periodeLibelle = "Période personnalisée";
            }
        } else {
            // Par défaut, la journée d'aujourd'hui
            $aujourdHui = Carbon::today()->format('Y-m-d');
            $query->whereDate('date_mouvement', $aujourdHui);
            $periodeLibelle = "Aujourd'hui (" . Carbon::today()->format('d/m/Y') . ")";
        }

        // Filtre par type d'opération si besoin
        if ($request->filled('type_operation')) {
            $query->where('type_operation', $request->type_operation);
        }

        // --- CALCUL DES KPI DE CAISSE ---
        $kpiQuery     = clone $query;
        $totalEntrees = (clone $kpiQuery)->where('sens', 'entree')->sum('montant');
        $totalSorties = (clone $kpiQuery)->where('sens', 'sortie')->sum('montant');

        // (Optionnel) Tu pourras y ajouter ton fond de caisse initial
        $soldeTheorique = $totalEntrees - $totalSorties;

        // Récupération des mouvements paginés (triés par date décroissante)
        $mouvements = $query->latest('date_mouvement')->paginate(15)->appends(request()->query());

        return view('admin.caisse.journal', compact('mouvements', 'totalEntrees', 'totalSorties', 'soldeTheorique', 'periodeLibelle'));
    }
}
