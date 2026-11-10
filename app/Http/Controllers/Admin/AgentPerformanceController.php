<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\AgentPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;

class AgentPerformanceController extends Controller
{
    public function index(Request $request, AgentPerformanceService $performance)
    {
        $filters = $this->validatedFilters($request);
        $report = $performance->monthly(
            $filters['annee'],
            $filters['mois'],
            $filters['agent_id'] ? (int) $filters['agent_id'] : null,
            $filters['tri']
        );

        $allRows = $report['rows'];
        $chartRows = $allRows
            ->filter(fn ($row) => $row['rate'] !== null)
            ->sortByDesc('rate')
            ->take(10)
            ->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rankings = new LengthAwarePaginator(
            $allRows->forPage($page, 25)->values(),
            $allRows->count(),
            25,
            $page,
            [
                'path' => $request->url(),
                'query' => $filters,
            ]
        );
        $agents = Agent::query()
            ->orderBy('nom')
            ->get(['id', 'nom', 'code_agent']);
        $monthLabel = $report['period']['start']->locale('fr')->translatedFormat('F Y');

        return view('admin.performance-terrain.index', compact(
            'report',
            'rankings',
            'chartRows',
            'agents',
            'filters',
            'monthLabel'
        ));
    }

    public function export(Request $request, AgentPerformanceService $performance)
    {
        $filters = $this->validatedFilters($request);
        $report = $performance->monthly(
            $filters['annee'],
            $filters['mois'],
            $filters['agent_id'] ? (int) $filters['agent_id'] : null,
            $filters['tri']
        );
        $filename = sprintf('classement-agents-%04d-%02d.csv', $filters['annee'], $filters['mois']);

        return response()->streamDownload(function () use ($report) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new RuntimeException('Impossible d’ouvrir le flux d’export CSV.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Rang',
                'Agent',
                'Code agent',
                'Statut agent',
                'Montant attendu (FCFA)',
                'Montant collecté (FCFA)',
                'Écart (FCFA)',
                'Taux atteint (%)',
                'Pointages',
                'Cycles',
                'Jours ouvrés retenus',
            ], ';');

            foreach ($report['rows'] as $row) {
                fputcsv($output, [
                    $row['rank'] ?? 'Hors classement',
                    $this->spreadsheetSafe($row['nom']),
                    $this->spreadsheetSafe($row['code_agent'] ?? ''),
                    $row['actif'] ? 'Actif' : 'Inactif',
                    $row['expected'],
                    $row['collected'],
                    $row['gap'],
                    $row['rate'] ?? '',
                    $row['pointages'],
                    $row['cycles'],
                    $row['weekdays'],
                ], ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'mois' => ['nullable', 'integer', 'between:1,12'],
            'annee' => ['nullable', 'integer', 'between:2020,2100'],
            'agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'tri' => ['nullable', 'in:rate,collected,expected,gap'],
        ]);

        return [
            'mois' => (int) ($validated['mois'] ?? now()->month),
            'annee' => (int) ($validated['annee'] ?? now()->year),
            'agent_id' => $validated['agent_id'] ?? null,
            'tri' => $validated['tri'] ?? 'rate',
        ];
    }

    private function spreadsheetSafe(?string $value): string
    {
        $value = $value ?? '';

        return preg_match('/^[\s]*[=+\-@]/', $value) ? "'" . $value : $value;
    }
}
