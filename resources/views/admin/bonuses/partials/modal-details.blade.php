<div class="modal fade" id="modalDetails{{ $data->agent_id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-circle me-2"></i>Détails : {{ $data->agent->nom }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                {{-- Entête interne --}}
                <div class="p-4 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold text-success mb-0">{{ number_format($data->total_global, 0, ',', ' ') }} F</h3>
                        <p class="text-muted mb-0 small">Total cumulé en attente</p>
                    </div>
                    <form action="{{ route('admin.bonuses.bulk-approve') }}" method="POST">
                        @csrf
                        <input type="hidden" name="agent_id" value="{{ $data->agent_id }}">
                        <button type="submit" class="btn btn-success px-4 shadow-sm" onclick="return confirm('Payer la totalité ?')">
                            <i class="bi bi-check2-all me-1"></i>Tout Valider & Payer
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Type</th>
                                <th>Motif</th>
                                <th class="text-center">Montant</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data->items as $item)
                            <tr>
                                <td class="ps-4 small">{{ \Carbon\Carbon::parse($item->date_attribution)->format('d/m/Y') }}</td>
                                <td>
                                    @if($item->commission_genere == 1)
                                        <span class="badge bg-info-subtle text-info border border-info">Auto</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary">Manuel</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $item->motif }}</span>
                                </td>
                                <td class="text-center fw-bold">{{ number_format($item->montant, 0, ',', ' ') }} F</td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        {{-- Valider Individuellement --}}
                                        <form action="{{ route('admin.bonuses.approve-single', $item->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success p-1 px-2" title="Valider cet élément">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>

                                        {{-- Refuser/Supprimer --}}
                                        <form action="{{ route('admin.bonuses.reject-single', $item->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Supprimer" onclick="return confirm('Supprimer cet élément ?')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light p-2">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>