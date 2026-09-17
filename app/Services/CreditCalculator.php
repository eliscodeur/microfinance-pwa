<?php
namespace App\Services;

use Carbon\Carbon;

class CreditCalculator
{
    /**
     * Calcule le taux applicable (gère l'ancienne et la nouvelle orthographe du taux manuel)
     */
    public static function calculateRate(float $taux, ?float $tauxManuel = null): float
    {
        return $tauxManuel !== null && $tauxManuel > 0 ? $tauxManuel : $taux;
    }

    /**
     * Génère le plan d'amortissement prévisionnel (Echeancier)
     */
    public static function buildSchedule(array $data): array
    {
        // Arrondi strict à 0 décimale pour la devise
        $montant = round((float) $data['montant_demande'], 0);

        // Récupération harmonisée de taux_manuel
        $tauxManuelInput = $data['taux_manuel'] ?? null;
        if ($tauxManuelInput === '' || $tauxManuelInput === '0') {
            $tauxManuelInput = null;
        }

        $taux = self::calculateRate((float) $data['taux'], $tauxManuelInput !== null ? (float) $tauxManuelInput : null) / 100;

        $nombre  = max(1, (int) $data['nombre_echeances']);
        $differe = max(0, (int) ($data['differe'] ?? 0));

        if ($differe >= $nombre) {
            $differe = 0;
        }

        $mode        = $data['mode'] ?? 'fixe';
        $periodicite = $data['periodicite'] ?? 'mensuelle';

        $currentDate = isset($data['date_debut']) ? Carbon::parse($data['date_debut']) : Carbon::today();
        $schedule    = [];

        $echeancesAmortissables = $nombre - $differe;
        $principalBase          = round($montant / $echeancesAmortissables, 0);
        $remaining              = $montant;

        $interetTotalGlobal = round($montant * $taux, 0);
        $interetParEcheance = round($interetTotalGlobal / $nombre, 0);

        for ($i = 1; $i <= $nombre; $i++) {

            if ($periodicite === 'journaliere') {
                $currentDate->addDay();
            } elseif ($periodicite === 'hebdomadaire') {
                $currentDate->addWeek();
            } elseif ($periodicite === 'quinzaine') {
                $currentDate->addDays(15);
            } else {
                $currentDate->addMonth();
            }

            if ($i === $nombre) {
                $totalDejaAmortiInteret = $interetParEcheance * ($nombre - 1);
                $interest               = round($interetTotalGlobal - $totalDejaAmortiInteret, 0);
            } else {
                $interest = $interetParEcheance;
            }

            if ($i <= $differe) {
                $principal = 0.0;
            } else {
                $principal = ($i === $nombre) ? round($remaining, 0) : $principalBase;
            }

            $total = round($principal + $interest, 0);

            $schedule[] = [
                'numero'     => $i,
                'date'       => $currentDate->toDateString(),
                'principal'  => $principal,
                'interest'   => $interest,
                'total'      => $total,
                'is_differe' => ($i <= $differe),
            ];

            $remaining = round($remaining - $principal, 0);
        }

        return $schedule;
    }

    /**
     * Cumule la totalité des intérêts calculés sur l'échéancier
     */
    public static function totalInterest(array $schedule): float
    {
        return array_reduce($schedule, fn($carry, $item) => $carry + $item['interest'], 0.0);
    }

    /**
     * Calcule la pénalité de retard journalière (0.1% par jour par défaut)
     */
    public static function calculatePenalty(float $amount, int $daysLate): float
    {
        if ($daysLate <= 0) {
            return 0.0;
        }

        $dailyRate = 0.001; // 0.1% par jour de retard

        // Arrondi à 0 décimale pour s'aligner sur le reste de la facturation
        return round($amount * $dailyRate * $daysLate, 0);
    }
}
