<?php

namespace App\Services;

use App\Models\Agent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgentPerformanceService
{
    public function monthly(int $year, int $month, ?int $agentId = null, string $sort = 'rate'): array
    {
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $today = CarbonImmutable::today();
        $periodEnd = $periodStart->isSameMonth($today)
            ? $today
            : $periodStart->endOfMonth();
        if ($periodStart->greaterThan($today)) {
            $periodEnd = $periodStart->subDay();
        }

        $weekdayDates = [];
        for ($date = $periodStart; $date->lte($periodEnd); $date = $date->addDay()) {
            if ($date->isWeekday()) {
                $weekdayDates[$date->toDateString()] = [
                    'label' => $date->format('d/m'),
                    'expected' => 0.0,
                    'collected' => 0.0,
                ];
            }
        }

        $agents = Agent::query()
            ->when($agentId, fn ($query) => $query->whereKey($agentId))
            ->orderBy('nom')
            ->get(['id', 'nom', 'code_agent', 'actif', 'user_id'])
            ->keyBy('id');
        $agentByUserId = $agents->filter(fn ($agent) => $agent->user_id)
            ->keyBy('user_id');
        $rows = [];
        foreach ($agents as $agent) {
            $rows[$agent->id] = [
                'agent_id' => (int) $agent->id,
                'nom' => $agent->nom,
                'code_agent' => $agent->code_agent,
                'actif' => (bool) $agent->actif,
                'expected' => 0.0,
                'collected' => 0.0,
                'gap' => 0.0,
                'pointages' => 0,
                'cycles' => 0,
                'cycle_ids' => [],
                'weekdays' => 0,
                'rate' => null,
                'rank' => null,
            ];
        }

        $cycles = DB::table('cycles')
            ->join('carnets', 'carnets.id', '=', 'cycles.carnet_id')
            ->where('cycles.statut', '!=', 'annule')
            ->whereDate('cycles.date_debut', '<=', $periodEnd->toDateString())
            ->where(function ($query) use ($periodStart) {
                $query->whereNull('cycles.date_cloture_reelle')
                    ->orWhereDate('cycles.date_cloture_reelle', '>=', $periodStart->toDateString());
            })
            ->get([
                'cycles.id',
                'cycles.carnet_id',
                'cycles.agent_id',
                'cycles.statut',
                'cycles.date_debut',
                'cycles.date_fin_prevue',
                'cycles.date_cloture_reelle',
                'cycles.montant_journalier',
                'cycles.nombre_jours_objectif',
                'carnets.agent_id as carnet_agent_id',
            ]);

        $histories = DB::table('carnet_agent_histories')
            ->whereIn('carnet_id', $cycles->pluck('carnet_id')->unique())
            ->orderBy('assigned_at')
            ->get(['carnet_id', 'agent_id', 'assigned_at', 'unassigned_at'])
            ->groupBy('carnet_id');

        $cycleContexts = [];
        foreach ($cycles as $cycle) {
            $cycleStart = CarbonImmutable::parse($cycle->date_debut)->startOfDay();
            $cycleEnd = $cycle->date_cloture_reelle
                ? CarbonImmutable::parse($cycle->date_cloture_reelle)->startOfDay()
                : ($cycle->statut === 'termine' && $cycle->date_fin_prevue
                    ? CarbonImmutable::parse($cycle->date_fin_prevue)->startOfDay()
                    : $periodEnd);
            $cycleEnd = $cycleEnd->min($periodEnd);

            if ($cycleStart->greaterThan($cycleEnd)) {
                continue;
            }

            $cycleAgentId = $this->resolveAgentId(
                (int) ($cycle->carnet_agent_id ?? 0),
                (int) ($cycle->agent_id ?? 0),
                $agents,
                $agentByUserId
            );
            $cycleHistories = $histories->get($cycle->carnet_id, collect());
            $cycleContexts[$cycle->id] = [
                'start' => $cycleStart,
                'end' => $cycleEnd,
                'agent_id' => $cycleAgentId,
                'histories' => $cycleHistories,
            ];

            if ($cycleStart->greaterThan($periodEnd) || $cycleEnd->lessThan($periodStart)) {
                continue;
            }

            $expectedStart = $cycleStart->max($periodStart);
            $expectedEnd = $cycleEnd->min($periodEnd);
            $daysBefore = $this->weekdaysBetween(
                $cycleStart,
                $expectedStart->subDay()
            );
            $objectiveDays = max(0, (int) ($cycle->nombre_jours_objectif ?? 0));
            $dailyAmount = max(0, (float) $cycle->montant_journalier);

            for ($date = $expectedStart; $date->lte($expectedEnd); $date = $date->addDay()) {
                if (! $date->isWeekday()) {
                    continue;
                }

                $cycleDay = $daysBefore + $this->weekdaysBetween($expectedStart, $date);
                if ($cycleDay > $objectiveDays || $dailyAmount <= 0) {
                    continue;
                }

                $ownerId = $this->assignedAgentForDate(
                    $cycleHistories,
                    $date,
                    $cycleAgentId
                );
                if (! $ownerId || ! isset($rows[$ownerId])) {
                    continue;
                }

                $rows[$ownerId]['expected'] += $dailyAmount;
                $rows[$ownerId]['weekdays']++;
                $rows[$ownerId]['cycle_ids'][$cycle->id] = true;
                $weekdayDates[$date->toDateString()]['expected'] += $dailyAmount;
            }
        }

        $collections = DB::table('collectes')
            ->join('cycles', 'cycles.id', '=', 'collectes.cycle_id')
            ->where('cycles.statut', '!=', 'annule')
            ->whereDate('collectes.date_saisie', '>=', $periodStart->toDateString())
            ->whereDate('collectes.date_saisie', '<=', $periodEnd->toDateString())
            ->whereDate('cycles.date_debut', '<=', $periodEnd->toDateString())
            ->selectRaw('collectes.cycle_id, collectes.agent_id, DATE(collectes.date_saisie) as jour, SUM(collectes.montant) as montant, SUM(collectes.pointage) as pointages')
            ->groupBy('collectes.cycle_id', 'collectes.agent_id', DB::raw('DATE(collectes.date_saisie)'))
            ->get();

        foreach ($collections as $collection) {
            $context = $cycleContexts[$collection->cycle_id] ?? null;
            if (! $context || ! isset($weekdayDates[$collection->jour])) {
                continue;
            }

            $date = CarbonImmutable::parse($collection->jour);
            if ($date->lt($context['start']) || $date->gt($context['end'])) {
                continue;
            }

            $collectorId = (int) $collection->agent_id;
            if (! isset($rows[$collectorId])) {
                continue;
            }

            $amount = (float) $collection->montant;
            $rows[$collectorId]['collected'] += $amount;
            $rows[$collectorId]['pointages'] += (int) $collection->pointages;
            $rows[$collectorId]['cycle_ids'][$collection->cycle_id] = true;
            $weekdayDates[$collection->jour]['collected'] += $amount;
        }

        $cycleIds = [];
        foreach ($rows as $row) {
            if ($row['expected'] > 0 || $row['collected'] > 0) {
                $cycleIds = array_merge($cycleIds, array_keys($row['cycle_ids']));
            }
        }
        $cycleCount = count(array_unique($cycleIds));

        foreach ($rows as &$row) {
            $row['cycles'] = count($row['cycle_ids']);
            unset($row['cycle_ids']);
            $row['expected'] = round($row['expected'], 2);
            $row['collected'] = round($row['collected'], 2);
            $row['gap'] = round($row['collected'] - $row['expected'], 2);
            $row['rate'] = $row['expected'] > 0
                ? round(($row['collected'] / $row['expected']) * 100, 1)
                : null;
        }
        unset($row);

        $this->sortRows($rows, $sort);
        $this->assignRanks($rows, $sort);

        $filteredRows = array_values(array_filter($rows, fn ($row) => $row['expected'] > 0 || $row['collected'] > 0));
        $expectedTotal = round(array_sum(array_column($filteredRows, 'expected')), 2);
        $collectedTotal = round(array_sum(array_column($filteredRows, 'collected')), 2);
        $rankedAgents = count(array_filter($filteredRows, fn ($row) => $row['expected'] > 0));

        return [
            'rows' => collect($filteredRows),
            'daily' => array_values($weekdayDates),
            'daily_labels' => array_column(array_values($weekdayDates), 'label'),
            'daily_expected' => array_column(array_values($weekdayDates), 'expected'),
            'daily_collected' => array_column(array_values($weekdayDates), 'collected'),
            'summary' => [
                'expected' => $expectedTotal,
                'collected' => $collectedTotal,
                'rate' => $expectedTotal > 0 ? round($collectedTotal / $expectedTotal * 100, 1) : null,
                'agents' => $rankedAgents,
                'cycles' => $cycleCount,
            ],
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
                'is_provisional' => $periodStart->isSameMonth($today),
            ],
        ];
    }

    private function weekdaysBetween(CarbonImmutable $start, CarbonImmutable $end): int
    {
        if ($start->greaterThan($end)) {
            return 0;
        }

        $days = $start->diffInDays($end) + 1;
        $fullWeeks = intdiv($days, 7);
        $weekdays = $fullWeeks * 5;

        for ($offset = 0, $remainder = $days % 7; $offset < $remainder; $offset++) {
            if ($start->addDays($fullWeeks * 7 + $offset)->isWeekday()) {
                $weekdays++;
            }
        }

        return $weekdays;
    }

    private function resolveAgentId(int $carnetAgentId, int $cycleAgentId, Collection $agents, Collection $agentByUserId): ?int
    {
        if ($carnetAgentId && $agents->has($carnetAgentId)) {
            return $carnetAgentId;
        }
        if ($cycleAgentId && $agentByUserId->has($cycleAgentId)) {
            return (int) $agentByUserId->get($cycleAgentId)->id;
        }
        if ($cycleAgentId && $agents->has($cycleAgentId)) {
            return $cycleAgentId;
        }

        return null;
    }

    private function assignedAgentForDate(Collection $histories, CarbonImmutable $date, ?int $fallbackAgentId): ?int
    {
        if ($histories->isEmpty()) {
            return $fallbackAgentId;
        }

        $endOfDay = $date->endOfDay();
        $activeHistory = $histories
            ->filter(function ($history) use ($endOfDay) {
                return CarbonImmutable::parse($history->assigned_at)->lte($endOfDay)
                    && (! $history->unassigned_at || CarbonImmutable::parse($history->unassigned_at)->gt($endOfDay));
            })
            ->sortByDesc('assigned_at')
            ->first();

        return $activeHistory ? (int) $activeHistory->agent_id : null;
    }

    private function sortRows(array &$rows, string $sort): void
    {
        usort($rows, function ($left, $right) use ($sort) {
            $leftValue = $left[$sort] ?? null;
            $rightValue = $right[$sort] ?? null;

            if ($leftValue === null || $rightValue === null) {
                if ($leftValue !== $rightValue) {
                    return $leftValue === null ? 1 : -1;
                }
            } elseif ($leftValue != $rightValue) {
                return $rightValue <=> $leftValue;
            }

            if ($left['rate'] !== $right['rate']) {
                return ($right['rate'] ?? -1) <=> ($left['rate'] ?? -1);
            }
            if ($left['collected'] !== $right['collected']) {
                return $right['collected'] <=> $left['collected'];
            }

            return strcasecmp($left['nom'], $right['nom']);
        });
    }

    private function assignRanks(array &$rows, string $sort): void
    {
        $rank = 0;
        $position = 0;
        $previousKey = null;

        foreach ($rows as &$row) {
            if ($row['rate'] === null) {
                continue;
            }
            $position++;
            $key = serialize($row[$sort] ?? null);
            if ($key !== $previousKey) {
                $rank = $position;
                $previousKey = $key;
            }
            $row['rank'] = $rank;
        }
        unset($row);
    }
}
