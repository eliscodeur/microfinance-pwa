<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Numéro</th>
                <th>Client</th>
                <th>Statut</th> {{-- Nouvelle colonne Statut --}}
                @if($type == 'tontine')
                    <th>Nombre de cycles</th>
                @else
                    <th>Liaison</th> {{-- Nouvelle colonne Liaison pour les comptes --}}
                @endif
                <th>État Crédit</th>
                <th>Solde Disponible</th>
                <th class="text-end pe-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $carnet)
            <tr>
                <td class="ps-3 fw-bold text-primary">{{ $carnet->numero }}</td>
                <td>
                    <span class="fw-bold">{{ $carnet->client->nom }} {{ $carnet->client->prenom }}</span><br>
                    <small class="text-muted">{{ $carnet->client->telephone }}</small>
                </td>

                {{-- Affichage du Statut du Carnet --}}
                <td>
                    @if($carnet->statut == 'actif')
                        <span class="badge bg-light text-success border border-success small">Actif</span>
                    @elseif($carnet->statut == 'fermé')
                        <span class="badge bg-light text-danger border border-danger small">Fermé</span>
                    @else
                        <span class="badge bg-light text-warning border border-warning small">{{ ucfirst($carnet->statut) }}</span>
                    @endif
                </td>

                @if($type == 'tontine')
                    {{-- Colonne Cycles --}}
                    <td>
                        <span class="badge bg-success" title="Nombre total de cycles">
                            {{ $carnet->cycles_count ?? $carnet->cycles->count() }}
                        </span>
                    </td>
                @else
                    {{-- Colonne Liaison pour les comptes épargne --}}
                    <td>
                        @if($carnet->parent)
                            <span class="text-dark small fw-bold">
                                <i class="bi bi-link-45deg"></i> Tontine n° {{ $carnet->parent->numero }}
                            </span>
                        @else
                            <span class="badge bg-light text-secondary border small">Indépendant</span>
                        @endif
                    </td>
                @endif

                {{-- État du Crédit --}}
                <td>
                    @php 
                        $creditsActifs = $carnet->credits->where('statut', 'active');
                        $aUnCredit = $creditsActifs->isNotEmpty();
                    @endphp

                    @if($aUnCredit)
                        <span class="badge rounded-pill bg-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i> Crédit en cours
                        </span>
                        <br>
                        <small class="text-danger fw-bold">
                            @php
                                $totalEncours = $creditsActifs->sum(function($c) {
                                    return ($c->montant_accorde + $c->interet_total) - $c->montant_rembourse;
                                });
                            @endphp
                            {{ number_format($totalEncours, 0, ',', ' ') }} F
                        </small>
                    @else
                        <span class="badge rounded-pill bg-light text-success border border-success">
                            <i class="bi bi-check-circle me-1"></i> Aucun crédit
                        </span>
                    @endif
                </td>

                @if($type == 'tontine')
                    <td class="fw-bold">
                        <i class="bi bi-arrow-down-circle me-1 text-primary"></i>
                        {{ number_format($carnet->solde_tontine_non_retire, 0, ',', ' ') }} F
                    </td>
                @else
                    <td class="fw-bold">
                        <i class="bi bi-piggy-bank me-1 text-warning"></i>
                        {{ number_format($carnet->solde_disponible, 0, ',', ' ') }} F
                    </td>
                @endif

                <td class="text-end pe-3">
                    <div class="btn-group">
                        <a href="{{ route('admin.carnets.show', $carnet->id) }}" class="btn btn-sm btn-outline-secondary" title="Voir détails">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                            data-id="{{ $carnet->id }}"
                            data-numero="{{ $carnet->numero }}"
                            data-client="{{ $carnet->client_id }}"
                            data-type="{{ $carnet->type }}"
                            data-date="{{ $carnet->date_debut }}"
                            data-parent="{{ $carnet->parent_id }}"
                            title="Modifier">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                {{-- Colspan ajusté à 7 (N°, Client, Statut, Cycles/Liaison, Crédit, Solde, Actions) --}}
                <td colspan="7" class="text-center py-4 text-muted">
                    Aucun carnet trouvé.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">
    {{ $carnets->links('pagination::bootstrap-5') }}
</div>