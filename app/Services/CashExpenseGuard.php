<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\MouvementCaisse;
use Illuminate\Support\Facades\Auth;

class CashExpenseGuard
{
    public function assertCanBeRecorded(int $userId, string $paymentMode, float $amount): ?CashSession
    {
        if (! MouvementCaisse::isCashPayment($paymentMode)) {
            return null;
        }

        $session = CashSession::where('user_id', $userId)
            ->where('statut', 'ouverte')
            ->lockForUpdate()
            ->first();

        if (! $session) {
            throw new \DomainException('Aucune session de caisse ouverte ne peut financer cette dépense en espèces.');
        }

        app(CashClosingService::class)->assertSufficientCash($session, $amount);

        return $session;
    }

    public function assertForAuthenticatedUser(string $paymentMode, float $amount): ?CashSession
    {
        return $this->assertCanBeRecorded((int) Auth::id(), $paymentMode, $amount);
    }
}
