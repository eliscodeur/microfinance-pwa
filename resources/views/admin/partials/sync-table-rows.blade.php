                @forelse($batches as $batch)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $batch->agent->nom ?? 'Agent' }}</div>
                            <div class="small text-muted">{{ $batch->agent->code_agent ?? '--' }}</div>
                        </td>
                        <!-- <td class="small">{{ $batch->sync_uuid }}</td> -->
                        <td>{{ $batch->nb_cycles }}</td>
                        <td>{{ $batch->nb_collectes }}</td>
                        <td>{{ number_format((float) $batch->total_montant, 0, ',', ' ') }} FCFA</td>
                        <td>
                           @php
                                $title = "en attente"; // Valeur par défaut
                                if($batch->status === "approved") {
                                    $title = "Approuvée";
                                } elseif($batch->status === "rejected") {
                                    $title = "rejetée";
                                } elseif($batch->status ==="cancelled") {
                                    $title = "Annulée";
                                }
                            @endphp
                            <span class="badge bg-{{ $batch->status === 'pending_review' ? 'warning text-dark' : ($batch->status === 'approved' ? 'success' : ($batch->status === 'rejected' ? 'danger' : 'secondary')) }}">
                                {{ $title }}
                            </span>
                        </td>
                        <td class="small">{{ $batch->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.sync-batches.show', $batch) }}" class="btn btn-sm btn-primary">Verifier</a>
                        </td>
                    </tr>
                @empty
                    
                @endforelse