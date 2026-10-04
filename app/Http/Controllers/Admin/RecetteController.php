<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recette;
use Illuminate\Http\Request;

class RecetteController extends Controller
{
    public function index(Request $request)
    {
        $query = Recette::with(['client', 'credit', 'user']);

        // 1. Gestion de la Période (Filtre ou Mois en cours par défaut)
        if ($request->filled('periode')) {
            // Nettoyage de l'URL (remplacement des + et encodages par des espaces)
            $periodeStr = urldecode(str_replace('+', ' ', $request->periode));

            // On découpe la chaîne en se basant sur le mot "au"
            $dates = preg_split('/\s+au\s+/i', trim($periodeStr));

            if (count($dates) == 2) {
                $dateDebut = trim($dates[0]);
                // On s'assure de prendre toute la journée de fin jusqu'à 23:59:59 si c'est stocké avec l'heure
                $dateFin = trim($dates[1]);

                $query->whereDate('date_recette', '>=', $dateDebut)
                    ->whereDate('date_recette', '<=', $dateFin);

                $periodeLibelle = "Du " . \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') . " au " . \Carbon\Carbon::parse($dateFin)->format('d/m/Y');
            } elseif (count($dates) == 1 && ! empty($dates[0])) {
                $dateUnique = trim($dates[0]);
                $query->whereDate('date_recette', $dateUnique);
                $periodeLibelle = "Le " . \Carbon\Carbon::parse($dateUnique)->format('d/m/Y');
            } else {
                $periodeLibelle = "Période personnalisée";
            }
        } else {
            // PAR DÉFAUT : Du 1er du mois jusqu'à aujourd'hui
            $debutMois = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
            $dateJour  = \Carbon\Carbon::now()->format('Y-m-d');

            $query->whereDate('date_recette', '>=', $debutMois)
                ->whereDate('date_recette', '<=', $dateJour);

            $periodeLibelle = "Mois en cours (au " . \Carbon\Carbon::now()->format('d/m/Y') . ")";
        }
        // Filtre par type de recette (si sélectionné)
        if ($request->filled('type_recette')) {
            $query->where('type_recette', $request->type_recette);
        }

        // --- KPI DYNAMIQUES ---
        $kpiQuery = clone $query;

        $totalMois            = (clone $kpiQuery)->sum('montant');
        $nombreOperationsMois = (clone $kpiQuery)->count();

        // Récupération des données paginées
        $recettes = $query->latest('date_recette')->paginate(15)->appends(request()->query());

        // Récupération de la liste distincte des types de recettes
        $typesRecettes = Recette::select('type_recette')->distinct()->pluck('type_recette');

        return view('admin.recettes.index', compact('recettes', 'typesRecettes', 'totalMois', 'nombreOperationsMois', 'periodeLibelle'));
    }
}
