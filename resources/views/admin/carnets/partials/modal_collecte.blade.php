<!-- Modal des Collectes avec Scroll -->
<div class="modal modal-lg fade" id="modalCollectes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-journal-check me-2"></i>Détails du Cycle <span id="modalCycleNumber"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-3">Date / Heure</th>
                            <th>Agent</th>
                            <th class="text-center">Pointage</th>
                            <th class="text-end pe-3">Montant</th>
                        </tr>
                    </thead>
                    <tbody id="collectesTableBody">
                        <!-- Rempli par JS -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>