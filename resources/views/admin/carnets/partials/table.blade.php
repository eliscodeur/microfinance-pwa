<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Numéro</th>
                <th>Client</th>
                @if($type == 'tontine')
                    <th>Nombre de cycles</th>
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

                @if($type == 'tontine')
                    {{-- Colonne Cycles --}}
                    <td>
                        <span class="badge bg-success" title="Nombre total de cycles">
                            {{ $carnet->cycles->count() }}
                        </span>
                    </td>
                @endif

                {{-- État du Crédit --}}
                <td>
                    @php 
                        $creditEnCours = $carnet->credits->where('statut', 'en_cours')->first(); 
                    @endphp

                    @if($creditEnCours)
                        <span class="badge rounded-pill bg-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i> Crédit en cours
                        </span>
                        <br>
                        <small class="text-danger fw-bold">{{ number_format($creditEnCours->montant_restant, 0, ',', ' ') }} F</small>
                    @else
                        <span class="badge rounded-pill bg-light text-success border border-success">
                            <i class="bi bi-check-circle me-1"></i> Aucun crédit
                        </span>
                    @endif
                </td>

                {{-- Solde Disponible --}}
                @if($type == 'tontine')
                    {{-- Solde tontine : Cycles terminés et non retirés --}}
                    <td class="fw-bold text-danger">
                        {{ number_format($carnet->solde_tontine_non_retire, 0, ',', ' ') }} F
                    </td>
                @else
                    {{-- Solde Épargne classique --}}
                    <td class="fw-bold text-success">
                        {{ number_format($carnet->solde_disponible, 0, ',', ' ') }} F
                    </td>
                @endif

                <td class="text-end pe-3">
                    <div class="btn-group">
                        <a href="{{ route('admin.carnets.show', $carnet->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                {{-- Colspan ajusté à 6 pour 'tontine' (N°, Client, Cycles, Crédit, Solde, Actions) 
                     et à 5 pour les autres types --}}
                <td colspan="{{ $type == 'tontine' ? 6 : 5 }}" class="text-center py-4 text-muted">
                    Aucun carnet trouvé.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>