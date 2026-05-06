<?php

namespace App\Services;

use Carbon\Carbon;

class CreditCalculator
{
    public static function calculateRate(float $taux, ?float $tauxManuel = null): float
    {
        return $tauxManuel !== null && $tauxManuel > 0 ? $tauxManuel : $taux;
    }

    public static function periodDays(string $periodicite): int
    {
        return $periodicite === 'quinzaine' ? 15 : 30;
    }

    public static function buildSchedule(array $data): array
    {
        $montant = (float) $data['montant_demande'];
        $taux = self::calculateRate((float) $data['taux'], isset($data['taux_manuelle']) ? (float) $data['taux_manuelle'] : null) / 100;
        $nombre = max(1, (int) $data['nombre_echeances']);
        $mode = $data['mode'] ?? 'fixe';
        $periodDays = self::periodDays($data['periodicite'] ?? 'mensuelle');
        $start = isset($data['date_debut']) ? Carbon::parse($data['date_debut']) : Carbon::today();
        $schedule = [];

        $principalBase = round($montant / $nombre, 2);
        $remaining = $montant;

        for ($i = 1; $i <= $nombre; $i++) {
            if ($mode === 'degressif') {
                $interest = round($remaining * $taux, 2);
            } else {
                $interest = round($montant * $taux, 2);
            }

            $principal = $i === $nombre ? round($remaining, 2) : $principalBase;
            $total = round($principal + $interest, 2);
            $schedule[] = [
                'numero' => $i,
                'date' => $start->copy()->addDays($i * $periodDays)->toDateString(),
                'principal' => $principal,
                'interest' => $interest,
                'total' => $total,
            ];

            $remaining = round($remaining - $principal, 2);
        }

        return $schedule;
    }

    public static function totalInterest(array $schedule): float
    {
        return array_reduce($schedule, fn($carry, $item) => $carry + $item['interest'], 0.0);
    }

    public static function calculatePenalty(float $amount, int $daysLate): float
    {
        if ($daysLate <= 0) {
            return 0.0;
        }

        $dailyRate = 0.001; // 0.1% par jour de retard
        return round($amount * $dailyRate * $daysLate, 2);
    }
}
