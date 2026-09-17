<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryAdvance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AvanceSalaireController extends Controller
{
    /**
     * Enregistrer une nouvelle avance sur salaire avec une transaction DB (via AJAX).
     */
    public function store(Request $request, $agentId)
    {
        $request->validate([
            'montant_total'   => 'required|numeric|min:0',
            'nombre_tranches' => 'required|integer|min:1',
            'motif'           => 'nullable|string|max:255',
        ]);

        try {
            $avance = DB::transaction(function () use ($request, $agentId) {
                $montantTotal   = $request->montant_total;
                $nombreTranches = $request->nombre_tranches;
                $montantMensuel = $montantTotal / $nombreTranches;

                return SalaryAdvance::create([
                    'advance_uid'     => Str::uuid(),
                    'agent_id'        => $agentId,
                    'montant_total'   => $montantTotal,
                    'montant_mensuel' => $montantMensuel,
                    'nombre_tranches' => $nombreTranches,
                    'tranches_payees' => 0,
                    'montant_restant' => $montantTotal,
                    'date_demande'    => now(),
                    'statut'          => 'en_attente',
                    'motif'           => $request->motif,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Avance enregistrée avec succès.',
                'avance'  => $avance,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une avance avec une transaction DB (via AJAX).
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $avance = SalaryAdvance::findOrFail($id);
                $avance->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Avance supprimée avec succès.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ], 500);
        }
    }
}
