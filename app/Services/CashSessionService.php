<?php
namespace App\Services;

use App\Models\CashSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashSessionService
{
    public function openSession(int $userId, float $fondInitial): CashSession
    {
        // Empêcher d'ouvrir une nouvelle session si une est déjà active
        $activeSession = CashSession::where('user_id', $userId)
            ->where('statut', 'ouverte')
            ->first();

        if ($activeSession) {
            throw new \Exception("Vous possédez déjà une session de caisse active.");
        }

        return DB::transaction(function () use ($userId, $fondInitial) {
            return CashSession::create([
                'ulid'                => strtolower((string) Str::ulid()),
                'user_id'             => $userId,
                'fond_caisse_initial' => $fondInitial,
                'date_ouverture'      => now(),
                'statut'              => 'ouverte',
            ]);
        });
    }

    public function hasActiveSession(int $userId): bool
    {
        return CashSession::where('user_id', $userId)
            ->where('statut', 'ouverte')
            ->exists();
    }
}
