@extends('admin.layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1">Clients</h2>
                <p class="text-muted mb-0">Suivi des clients, de leurs affectations et de leurs carnets.</p>
            </div>
            <div class="d-flex gap-2">
                @can('Gérer Clients')
                    <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">Ajouter client</a>
                @endcan
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table id="clientsTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Adresse</th>
                            <th>Carnets</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($clients as $client)
                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        {{ $client->nom }} {{ $client->prenom }}
                                    </div>

                                </td>

                                <td>
                                    {{ $client->telephone ?: '---' }}
                                </td>

                                <td>
                                    {{ $client->adresse ?: '---' }}
                                </td>

                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $client->carnets_count }}
                                    </span>

                                    @if ($client->carnets_count > 0)
                                        <div class="small text-muted mt-1">
                                            {{ $client->carnets->take(2)->pluck('numero')->implode(', ') }}

                                            @if ($client->carnets_count > 2)
                                                ...
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">

                                        <a href="{{ route('admin.clients.show', $client->ulid) }}"
                                            class="btn btn-sm btn-outline-info">
                                            Détails
                                        </a>

                                        @can('Modifier données')
                                            <a href="{{ route('admin.clients.edit', $client->ulid) }}"
                                                class="btn btn-sm btn-outline-warning">
                                                Modifier
                                            </a>
                                        @endcan

                                        {{-- @can('Gérer Clients')
                                            <form id="form-delete-client-{{ $client->id }}" method="POST"
                                                action="{{ route('admin.clients.destroy', $client->id) }}"
                                                style="display: inline;">

                                                @csrf
                                                @method('DELETE')
                                                
                                                <button type="button" class="btn btn-sm btn-outline-danger js-delete-client"
                                                    data-client-id="{{ $client->id }}">
                                                    Supprimer
                                                </button>
                                            </form>
                                        @endcan --}}

                                    </div>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        ```

    </div>

    {{-- jQuery --}}

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables --}}

    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>

    {{-- DataTables Buttons --}}

    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.min.js"></script>

    {{-- JSZip nécessaire pour Excel --}}

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    {{-- Export Excel --}}

    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>

    {{-- SweetAlert --}}

    <script>
        function confirmerSuppressionClient(clientId) {
            Swal.fire({
                title: 'Confirmer la suppression',
                text: "Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    document
                        .getElementById(`form-delete-client-${clientId}`)
                        .submit();
                }
            });
        }

        document.querySelectorAll('.js-delete-client').forEach((button) => {
            button.addEventListener('click', () => {
                confirmerSuppressionClient(button.dataset.clientId);
            });
        });
    </script>

    {{-- DataTables --}}

    <script>
        $(document).ready(function() {

            var table = $('#clientsTable').DataTable({

                language: {
                    emptyTable: "Aucune donnée disponible",
                    info: "Affichage de _START_ à _END_ sur _TOTAL_ clients",
                    infoEmpty: "Affichage de 0 à 0 sur 0 client",
                    infoFiltered: "(filtré à partir de _MAX_ clients)",
                    lengthMenu: "Afficher _MENU_ clients",
                    loadingRecords: "Chargement...",
                    processing: "Traitement...",
                    search: "Rechercher :",
                    zeroRecords: "Aucun client trouvé",
                    paginate: {
                        first: "Premier",
                        last: "Dernier",
                        next: "Suivant",
                        previous: "Précédent"
                    }
                },

                // 'simple_numbers' fait ressortir les numéros de page (1, 2, 3...) + Précédent/Suivant
                pagingType: 'simple_numbers',

                // Disposition propre compatible Bootstrap 5
                dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',

                buttons: [{
                        text: '<i class="fas fa-file-excel me-1"></i> Exporter Filtré (Excel)',
                        className: 'btn btn-success btn-sm me-2',
                        action: function(e, dt) {
                            // Récupère la recherche courante dans DataTables et le filtre d'agent s'il existe
                            var searchParam = encodeURIComponent(dt.search());
                            var agentParam = $('#agent_id').val() || '';

                            // Envoie la requête au contrôleur Laravel (Ce qui déclenche la sauvegarde en BDD)
                            window.location.href = "{{ url('admin/clients/export/excel') }}" +
                                "?search=" + searchParam +
                                "&agent_id=" + agentParam;
                        }
                    },
                    {
                        text: '<i class="fas fa-database me-1"></i> Exporter TOUT (Excel)',
                        className: 'btn btn-success btn-sm',
                        action: function() {
                            // Passe all=1 pour tout exporter sans aucun filtre
                            window.location.href = "{{ url('admin/clients/export/excel') }}?all=1";
                        }
                    }
                ],

                pageLength: 10,

                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tous"]
                ],

                order: [
                    [0, 'asc']
                ],

                columnDefs: [{
                    orderable: false,
                    searchable: false,
                    targets: 4 // La colonne des actions ou boutons
                }]

            });

        });
    </script>
@endsection
