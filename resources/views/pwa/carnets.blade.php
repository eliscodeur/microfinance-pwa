
@extends('pwa.layouts.app')

@section('content')
<div class="container py-4">
    <h5 class="fw-bold mb-4 text-primary" id="clientName">Chargement...</h5>

    <div id="collecte-content">
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
        </div>
    </div>
</div>


<script type="module">
    import { db } from "{{ asset('js/db-manager.js') }}";
   
    const urlParams = new URLSearchParams(window.location.search);
    const clientId = parseInt(urlParams.get('client_id'));

    async function initCollectePage() {
        if (!clientId) return;

        try {
            if (!db.isOpen()) await db.open();

            const [client, carnets] = await Promise.all([
                db.clients.get(Number(clientId)),
                db.carnets.where('client_id').equals(Number(clientId)).toArray()
            ]);

            const container = document.getElementById('collecte-content');
            if (client) document.getElementById('clientName').innerText = client.nom;

            if (carnets.length === 0) {
                container.innerHTML = `<div class="text-center py-5 text-muted">Aucun carnet trouvé.</div>`;
                return;
            }

            let html = `<p class="text-muted small mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;">Mes Livrets d'Épargne</p>`;

            for (const carnet of carnets) {
                const cyclesDuCarnet = await db.cycles.where('carnet_id').equals(carnet.id).toArray() || [];
                
                const cycleActif = cyclesDuCarnet.find(cy => cy.statut === 'en_cours');
                const cyclesAencaisser = cyclesDuCarnet.filter(cy => cy.statut === 'termine' && !cy.retire_at);
                
                // --- CALCUL DU SOLDE DISPONIBLE ---
                let soldeRetirable = 0;
                for (const cy of cyclesAencaisser) {
                    const collectesDuCycle = await db.collectes.where('cycle_uid').equals(String(cy.cycle_uid)).toArray();
                    const totalCollectes = collectesDuCycle.reduce((sum, c) => sum + (Number(c.montant) || 0), 0);
                    const commission = Number(cy.montant_journalier || 0);
                    
                    if (totalCollectes > 0) {
                        soldeRetirable += Math.max(0, totalCollectes - commission);
                    }
                }

                // --- CALCUL DE L'ÉPARGNE DU CYCLE EN COURS ---
                let epargneEnCours = 0;
                let pourcentage = 0;
                
                if (cycleActif) {
                    const collectesActives = await db.collectes.where('cycle_uid').equals(String(cycleActif.cycle_uid)).toArray();
                    epargneEnCours = collectesActives.reduce((sum, item) => sum + (Number(item.montant) || 0), 0);
                    
                    // Utilisation de la somme des pointages pour la barre de progression (plus précis)
                    const nbrPointages = collectesActives.reduce((sum, item) => sum + (Number(item.pointage) || 0), 0);
                    pourcentage = Math.min(100, (nbrPointages / 31) * 100);
                }
                
                // --- LOGIQUE DEMANDÉE : CALCUL DES STATS ---
                // On prend le total historique du carnet (serveur)
                const totalHistorique = Number(carnet.total_cycles_termines || 0);
                const terminauxEnAttente = cyclesAencaisser.length;
                
                // Les payés = Total historique - ceux qui attendent encore d'être payés
                const terminauxPayes = Math.max(0, totalHistorique - terminauxEnAttente);

                html += `
                <div class="card mb-4 shadow-sm border-0" 
                    style="border-radius: 25px; background: #fff; overflow: hidden;"
                    onclick="window.goToPointage(${carnet.id})">
                    
                    <div class="p-3 d-flex justify-content-between align-items-center" style="background: rgba(13, 110, 253, 0.05);">
                        <span class="badge rounded-pill bg-primary px-3 shadow-sm">LIVRET N° ${carnet.numero || '---'}</span>
                        <div class="text-end">
                            <small class="text-muted d-block" style="font-size: 0.65rem; font-weight: 700;">SOLDE À REVERSER</small>
                            <span class="fw-bold text-success fs-5">${soldeRetirable.toLocaleString()} F</span>
                        </div>
                    </div>

                    <div class="card-body">
                        ${cycleActif ? `
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Cycle en cours</small>
                                    <h3 class="fw-black mb-0" style="color: #2d3436; font-size: 1.8rem;">
                                        ${epargneEnCours.toLocaleString()} <small style="font-size: 0.9rem;">FCFA</small>
                                    </h3>
                                </div>
                                <div class="col-5 text-end">
                                    <div class="d-inline-block p-2 rounded-3 bg-light border shadow-xs">
                                        <small class="d-block text-muted" style="font-size: 0.6rem;">Mise</small>
                                        <span class="fw-bold text-primary">${cycleActif.montant_journalier} F</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="progress" style="height: 10px; border-radius: 10px; background: #f0f2f5;">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: ${pourcentage}%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted" style="font-size: 0.7rem;">Objectif : 31 jours</small>
                                    <small class="fw-bold text-primary" style="font-size: 0.7rem;">${Math.round(pourcentage)}%</small>
                                </div>
                            </div>
                        ` : `
                            <div class="py-4 text-center text-muted small">Aucun cycle actif. Prêt pour une nouvelle épargne ?</div>
                        `}
                    </div>

                    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center pb-3 pt-0">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-check2-all text-success"></i> ${terminauxPayes} payé(s) | 
                            <i class="bi bi-clock text-warning"></i> ${terminauxEnAttente} en attente
                        </small>
                        <span class="text-primary fw-bold small" style="font-size: 0.8rem;">Gérer <i class="bi bi-chevron-right ms-1"></i></span>
                    </div>
                </div>`;
            }

            container.innerHTML = html;

        } catch (error) {
            console.error("Erreur Init Livret:", error);
            document.getElementById('collecte-content').innerHTML = `<div class="alert alert-danger">Erreur de chargement.</div>`;
        }
    }

    window.goToPointage = (carnetId) => {
        window.location.href = `/pwa/pointage-shell?carnet_id=${carnetId}`;
    };

    document.addEventListener('DOMContentLoaded', initCollectePage);
</script>
@endsection
