@extends('pwa.layouts.app')

@section('content')
<div class="container py-3">
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px; background: linear-gradient(135deg, #6c757d, #343a40); color: white;">
        <div class="card-body p-4 text-center">
            <div class="bg-white bg-opacity-25 rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                <i class="bi bi-shield-lock fs-3"></i>
            </div>
            <h4 class="fw-bold mb-1">Sécurité</h4>
            <p class="mb-0 opacity-75 small">Modifier mon code PIN agent</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
        <div class="mb-3">
            <label class="small fw-bold text-muted mb-2">ANCIEN CODE PIN</label>
            <input type="password" id="old-pin" class="form-control form-control-lg text-center" 
                    inputmode="numeric" maxlength="4" 
                   style="letter-spacing: 0.5rem; border-radius: 15px; background: #f8f9fa;">
        </div>

        <div class="mb-3">
            <label class="small fw-bold text-muted mb-2">NOUVEAU CODE PIN</label>
            <input type="password" id="new-pin" class="form-control form-control-lg text-center border-primary" 
                   inputmode="numeric" maxlength="4" 
                   style="letter-spacing: 0.5rem; border-radius: 15px;">
        </div>

        <div class="mb-4">
            <label class="small fw-bold text-muted mb-2">CONFIRMER LE NOUVEAU PIN</label>
            <input type="password" id="confirm-pin" class="form-control form-control-lg text-center border-primary" 
                  inputmode="numeric" maxlength="4" 
                   style="letter-spacing: 0.5rem; border-radius: 15px;">
        </div>

        <button onclick="validerChangementPIN()" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" 
                style="border-radius: 15px; height: 60px;">
            METTRE À JOUR LE PIN
        </button>
        
        <div class="text-center mt-3">
            <a href="/pwa/dashboard" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>
    </div>
</div>

<script type="module">
    import { getAgentDB } from '/js/db-manager.js';

    // Rendre la fonction accessible globalement
    window.validerChangementPIN = async () => {
        const oldPin = document.getElementById('old-pin').value;
        const newPin = document.getElementById('new-pin').value;
        const confirmPin = document.getElementById('confirm-pin').value;
        const matricule = localStorage.getItem('current_agent_matricule');
        const PIN_SALT = "NANA_SYSTEM_SECURE_2026";

        const notifierErreur = (message) => {
            // Force l'arrêt de n'importe quel loader en cours
            Swal.hideLoading(); 
            
            Swal.fire({
                icon: 'error',
                title: 'Oups...',
                text: message,
                confirmButtonText: 'D’accord',
                showConfirmButton: true,
                confirmButtonColor: '#0d6efd',
                allowOutsideClick: true,
                showClass: { popup: 'animate__animated animate__bounceIn' }
            });
        };

        // 1. Validations de base
        if (!oldPin || !newPin || !confirmPin) return notifierErreur("Veuillez remplir tous les champs.");
        if (newPin !== confirmPin) return notifierErreur("Les nouveaux codes PIN ne correspondent pas.");
        if (newPin.length !== 4) return notifierErreur("Le PIN doit comporter exactement 4 chiffres.");
        if (oldPin === newPin) return notifierErreur("Le nouveau PIN doit être différent de l'ancien.");

        try {
            Swal.fire({
                title: 'Traitement...',
                allowOutsideClick: false,
                didOpen: () => { 
                    Swal.showLoading(); 
                }
            });

            const activeDB = getAgentDB();
            const agentKey = `auth_v1_${matricule}`;
            let authObj = JSON.parse(localStorage.getItem(agentKey));

            if (!authObj) {
                Swal.close(); 
                setTimeout(() => {
                    notifierErreur("Session introuvable. Reconnectez-vous.");
                }, 100);
            }
            
            // 2. Vérification de l'ancien PIN
            const oldHashSaisi = CryptoJS.SHA256(oldPin + matricule + PIN_SALT).toString();
            
            if (authObj.pin_hash !== oldHashSaisi) {
                Swal.close(); 
                setTimeout(() => {
                    notifierErreur("L'ancien code PIN est incorrect.");
                }, 100);
            }

            // 3. Génération du nouveau Hash
            const newHash = CryptoJS.SHA256(newPin + matricule + PIN_SALT).toString();

            // 4. Mise à jour dans Dexie
            const updateCount = await activeDB.agents.where('matricule').equals(matricule).modify({
                pin_hash: newHash,
                synced: 0, 
                updated_at: new Date().toISOString()
            });

            if (updateCount === 0) throw new Error("Agent non trouvé");

            // 5. Mise à jour Session
            authObj.pin_hash = newHash;
            localStorage.setItem(agentKey, JSON.stringify(authObj));

            // --- SUCCÈS ---
            Swal.fire({
                icon: 'success',
                title: 'Mis à jour !',
                text: 'Votre code PIN a été modifié avec succès.',
                showConfirmButton: true,
                confirmButtonText: 'Continuer',
                confirmButtonColor: '#198754',
                timer: 3000,
                timerProgressBar: true
            });

            document.getElementById('old-pin').value = "";
            document.getElementById('new-pin').value = "";
            document.getElementById('confirm-pin').value = "";

        } catch (error) {
            console.error("Erreur technique:", error);
            notifierErreur("Erreur lors de la sauvegarde locale.");
        }
    };
</script>
@endsection