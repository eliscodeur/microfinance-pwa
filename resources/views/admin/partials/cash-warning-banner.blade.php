@inject('sessionService', 'App\Services\CashSessionService')

@php
    // Récupère le message personnalisé passé par la vue, ou utilise un message générique par défaut
    $warningMessage = $cashWarningMessage ?? 'Vous n\'avez pas de session de caisse active. Les opérations financières sont suspendues.';
@endphp

@if(auth()->check() && !$sessionService->hasActiveSession(auth()->id()))
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: #fff3cd; color: #856404; border-left: 5px solid #ffc107 !important;">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                <div>
                    <strong>Attention, caisse fermée !</strong>
                    <span>{{ $warningMessage }}</span>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.caisse.ouvrir') }}" class="btn btn-warning btn-sm fw-bold px-3 shadow-sm">
                    <i class="fas fa-key me-1"></i> Ouvrir ma caisse
                </a>
            </div>
        </div>
    </div>
@endif
