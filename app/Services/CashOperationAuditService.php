<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Models\Audit;
use Throwable;

class CashOperationAuditService
{
    public function recordRejectedExpense(
        int $userId,
        ?int $cashSessionId,
        array $input,
        Throwable $exception,
        string $url,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $auditableType = $cashSessionId ? CashSession::class : User::class;
        $auditableId = $cashSessionId ?: $userId;

        Audit::create([
            'user_type' => User::class,
            'user_id' => $userId,
            'event' => 'cash_expense_rejected',
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => [],
            'new_values' => [
                'operation' => $input['operation'] ?? 'expense',
                'result' => 'rejected',
                'reason' => $exception->getMessage(),
                'error_type' => class_basename($exception),
                'validation_errors' => $exception instanceof ValidationException
                    ? $exception->errors()
                    : null,
                'periode' => $input['periode'] ?? null,
                'credit_id' => $input['credit_id'] ?? null,
                'type_carnet' => $input['type_carnet'] ?? null,
                'quantite' => $input['quantite'] ?? null,
                'categories_charge_id' => $input['categories_charge_id'] ?? null,
                'montant' => $input['montant'] ?? null,
                'prix_unitaire_achat' => $input['prix_unitaire_achat'] ?? null,
                'date_depense' => $input['date_depense'] ?? null,
                'beneficiaire' => $input['beneficiaire'] ?? null,
                'mode_paiement' => $input['mode_paiement'] ?? null,
                'reference_piece' => $input['reference_piece'] ?? null,
                'motif' => isset($input['motif']) && is_string($input['motif'])
                    ? mb_substr($input['motif'], 0, 2000)
                    : null,
            ],
            'url' => $url,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 1023) : null,
            'tags' => 'caisse,dépense,refusée',
        ]);
    }
}
