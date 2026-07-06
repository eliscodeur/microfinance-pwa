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
        
        // Compatibilité ascendante pour sécuriser taux_manuel et taux_manuelle
        $tauxManuelInput = $data['taux_manuel'] ?? $data['taux_manuelle'] ?? null;
        $taux = self::calculateRate((float) $data['taux'], $tauxManuelInput !== null ? (float)$tauxManuelInput : null) / 100;
        
        $nombre = max(1, (int) $data['nombre_echeances']);
        $differe = max(0, (int) ($data['differe'] ?? 0));

        // Sécurité microfinance : le différé ne peut pas être supérieur ou égal au nombre d'échéances
        if ($differe >= $nombre) {
            $differe = 0;
        }

        $mode = $data['mode'] ?? 'fixe';
        $periodicite = $data['periodicite'] ?? 'mensuelle';
        
        // Initialisation de la date de départ
        $currentDate = isset($data['date_debut']) ? Carbon::parse($data['date_debut']) : Carbon::today();
        $schedule = [];

        // Le capital total est amorti uniquement sur les échéances restantes après le différé
        $echeancesAmortissables = $nombre - $differe;
        
        // Base d'amortissement avec arrondi strict
        $principalBase = round($montant / $echeancesAmortissables, 0);
        $remaining = $montant;

        for ($i = 1; $i <= $nombre; $i++) {
            
            // 1. Progression précise de la date selon les règles de l'institution
            if ($periodicite === 'journaliere') {
                $currentDate->addDay();
            } elseif ($periodicite === 'hebdomadaire') {
                $currentDate->addWeek();
            } elseif ($periodicite === 'quinzaine') {
                $currentDate->addDays(15);
            } else {
                $currentDate->addMonth(); // Mensuelle : préserve le même jour du mois
            }

            // 2. Calcul de la part des intérêts (arrondi à 0 décimale)
            if ($mode === 'degressif') {
                // Sur le capital restant dû (reste constant pendant le différé car aucun capital n'est versé)
                $interest = round($remaining * $taux, 0);
            } else {
                // Mode fixe / constant : toujours basé sur le capital initial
                $interest = round($montant * $taux, 0);
            }

            // 3. Gestion de la part du capital (Principal) avec intégration du différé
            if ($i <= $differe) {
                // Phase de différé : pas de remboursement du capital principal
                $principal = 0.0;
            } else {
                // Phase d'amortissement : la dernière échéance récupère le reliquat exact pour éviter les écarts d'arrondi
                $principal = ($i === $nombre) ? round($remaining, 0) : $principalBase;
            }

            // 4. Total de l'échéance de la période (entier)
            $total = round($principal + $interest, 0);

            $schedule[] = [
                'numero' => $i,
                'date' => $currentDate->toDateString(),
                'principal' => $principal,
                'interest' => $interest,
                'total' => $total,
                'is_differe' => ($i <= $differe), // Flag pratique pour identifier la période de grâce
            ];

            // Déduction du principal payé du capital restant dû
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