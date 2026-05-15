<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Connexion Agent - NANA Eco Consulting</title>
    
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
     <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>
   
    <style>
        :root { --nna-blue: #0d6efd; --nna-bg: #f8f9fa; }
        body { background-color: var(--nna-bg); min-height: 100vh; display: flex; align-items: center; }
        
        .login-card { border: none; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); transition: transform 0.2s; }
        
        .logo-icon { 
            width: 75px; height: 75px; background: var(--nna-blue); color: white; 
            border-radius: 22px; display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 20px; font-size: 2.2rem;
        }

        .form-control { 
            border-radius: 14px; padding: 12px; border: 2px solid #edf0f5; 
            background-color: #fcfdfe; transition: all 0.3s;
        }
        .form-control:focus { border-color: var(--nna-blue); box-shadow: none; background-color: #fff; }

        .input-group-text { border-radius: 14px; border: 2px solid #edf0f5; background-color: #fcfdfe; }

        .btn-login { 
            background-color: var(--nna-blue); border: none; border-radius: 14px; 
            padding: 14px; font-weight: 700; transition: all 0.2s;
        }
        .btn-login:active { transform: scale(0.96); }

        #install-banner {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1050;
            background: linear-gradient(90deg, #1a56a6, #0d6efd);
            color: white; padding: 12px; font-size: 0.85rem;
            display: none; align-items: center; justify-content: center;
        }

        .password-toggle { cursor: pointer; border-left: none !important; }

        @media (max-height: 650px) {
            body { align-items: flex-start; padding-top: 30px; }
        }
    </style>
</head>
<body>

    <!-- Bannière d'installation PWA -->
    <!-- <div id="install-banner" class="shadow-sm">
        <i class="bi bi-cloud-arrow-down-fill me-2"></i>
        <span>Accéder au mode hors-ligne</span>
        <button id="btn-install-now" class="btn btn-light btn-sm fw-bold ms-3" style="border-radius: 8px;">Installer</button>
    </div> -->

    <div class="container my-auto">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-6 col-lg-4">
                
                <div class="text-center mb-4">
                    <div class="logo-icon shadow-lg">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h2 class="fw-extrabold text-dark mb-1">Espace Agent</h2>
                    <p class="text-muted small">NANA Eco Consulting - Gestion Tontine</p>
                </div>

                <div class="card login-card p-4">
                    <form id="pwa-login-form">
                        @csrf
                        <div id="error-alert" class="alert alert-danger d-none py-2 px-3 small rounded-3"></div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Matricule Agent</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 text-primary"><i class="bi bi-hash"></i></span>
                                <input type="text" id="username" name="username" 
                                       class="form-control border-start-0" 
                                       placeholder="Ex: NNC-00005" 
                                       inputmode="text" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 text-primary"><i class="bi bi-lock"></i></span>
                                <input type="password" id="password" name="password" 
                                       class="form-control border-start-0 border-end-0" 
                                       placeholder="••••••••" required>
                                <span class="input-group-text bg-white password-toggle" onclick="togglePassword()">
                                    <i id="toggle-icon" class="bi bi-eye text-muted"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" id="btn-submit" class="btn btn-primary btn-login w-100 shadow d-flex justify-content-center align-items-center gap-2">
                            <span id="btn-text">Se connecter</span>
                            <div id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                            <i id="btn-icon" class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <p class="text-center mt-4 text-muted small">
                    v2.1.0 &bull; Mode Hors-ligne disponible
                </p>

            </div>
        </div>
    </div>
    <div class="modal fade" id="pinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body text-center p-4">
                    <i class="bi bi-shield-lock text-primary fs-1 mb-3"></i>
                    <h5 class="fw-bold">Sécurisez votre accès</h5>
                    <p class="text-muted small">Créez un code PIN à 4 chiffres pour vos collectes sur le terrain.</p>
                    
                    <div class="mb-4">
                        <input type="password" id="new-pin" class="form-control form-control-lg text-center fw-bold" 
                            placeholder="0 0 0 0" maxlength="4" inputmode="numeric" 
                            style="letter-spacing: 1rem; border-radius: 12px; border: 2px solid #eee;">
                    </div>

                    <button type="button" id="confirm-pin-btn" class="btn btn-primary w-100 py-3 fw-bold" 
                            style="border-radius: 12px;">
                        Confirmer et Synchroniser
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <script>
        // Fonction pour afficher/masquer le mot de passe
        function togglePassword() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('toggle-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        // Simulation du chargement à la soumission
        document.getElementById('pwa-login-form').addEventListener('submit', function(e) {
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const btnIcon = document.getElementById('btn-icon');
            
            btnText.innerText = "Vérification...";
            btnSpinner.classList.remove('d-none');
            btnIcon.classList.add('d-none');
            // Le reste de ta logique AJAX/Fetch ici
        });
    </script>
</body>
</html>

<script src="{{ asset('js/dexie.js') }}"></script>

<script type="module">
    import { getAgentDB } from '/js/db-manager.js';

    // Sel de sécurité pour le hachage
    const PIN_SALT = "NANA_SYSTEM_SECURE_2026";

    // --- INITIALISATION SÉCURISÉE DES MODAUX ---
    let pinModalInstance = null;

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('pinModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            pinModalInstance = new bootstrap.Modal(modalEl, {
                backdrop: 'static', // Empêche de fermer en cliquant à côté
                keyboard: false
            });
        }
    });
    async function refreshToken() {
        try {
            const response = await fetch('/refresh-csrf');
            const data = await response.json();
            // On met à jour la balise meta (assure-toi qu'elle existe dans ton <head>)
            let meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                meta.setAttribute('content', data.token);
            }
            return data.token;
        } catch (e) {
            console.error("Échec du rafraîchissement CSRF", e);
        }
    }
    document.getElementById('pwa-login-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const elements = {
            matricule: document.getElementById('username').value.trim(),
            password: document.getElementById('password').value,
            alertBox: document.getElementById('error-alert'),
            btn: document.getElementById('btn-submit'),
            btnText: document.getElementById('btn-text'),
            spinner: document.getElementById('btn-spinner')
        };

        const setUIState = (loading, text = "Vérification...") => {
            elements.btn.disabled = loading;
            elements.btnText.innerText = text;
            elements.spinner.classList.toggle('d-none', !loading);
            if (loading) elements.alertBox.classList.add('d-none');
        };

        try {
            if (typeof CryptoJS === 'undefined') throw new Error("Erreur : Bibliothèque de sécurité manquante.");

            const agentKey = `auth_v1_${elements.matricule}`;
            const localAuth = JSON.parse(localStorage.getItem(agentKey));

            // --- CAS 1 : CONNEXION OFFLINE (Local) ---
            if (localAuth && localAuth.pin_hash) {
                const inputHash = CryptoJS.SHA256(elements.password + elements.matricule + PIN_SALT).toString();

                if (inputHash === localAuth.pin_hash) {
                    if (navigator.onLine) {
                       checkStatus(elements.matricule)
                    }
                    setUIState(true, "Accès autorisé...");
                    localStorage.setItem('current_agent_matricule', elements.matricule);
                    localStorage.setItem('session_active', true);
                    window.location.href = "/pwa/dashboard";
                    return; 
                } else {
                    throw new Error("Code PIN incorrect pour ce compte.");
                }
            }

            // --- CAS 2 : CONNEXION ONLINE (Initialisation) ---
            if (!navigator.onLine) throw new Error("Internet requis pour configurer cet appareil.");

            setUIState(true, "Vérification serveur...");
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch("{{ route('agent.login.submit') }}", {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken 
                },
                body: JSON.stringify({ username: elements.matricule, password: elements.password })
            });

            const data = await response.json();
   
            if (!response.ok) throw new Error(data.message || "Identifiants invalides.");
            if(data.agent.actif == false) throw new Error("Votre compte a été révoqué");
            if(data.agent.sync == false) throw new Error("Votre compte n'est pas autorisé à synchroniser les données");
            // Récupération du PIN_HASH serveur
            const serverPinHash = data.agent.pin_hash; 
            
            // On remet le bouton à l'état normal car Swal va prendre la main
            setUIState(false, "Se connecter");

            if (serverPinHash) {
                // --- ÉTAPE : VÉRIFICATION DU PIN EXISTANT (Recovery) ---
                const { value: pinSaisi } = await Swal.fire({
                    title: 'Vérification du code PIN',
                    text: 'Entrez votre PIN à 4 chiffres pour valider cet appareil.',
                    input: 'password',
                    inputAttributes: { maxlength: 4, inputmode: 'numeric', pattern: '[0-9]*' },
                    showCancelButton: true,
                    confirmButtonText: 'Valider',
                    cancelButtonText: 'Annuler',
                    allowOutsideClick: false
                });

                if (!pinSaisi) return; // Annulation de l'agent

                const inputHash = CryptoJS.SHA256(pinSaisi + elements.matricule + PIN_SALT).toString();

                if (inputHash === serverPinHash) {
                     setUIState(true, "Initialisation...");
                    // SUCCESS : On finalise avec le hash serveur
                    return finalizeLogin(null, data.agent, elements.matricule, data.token, true, serverPinHash);
                } else {
                    // ÉCHEC : Message d'erreur et on reste sur le login
                    await Swal.fire({
                        icon: 'error',
                        title: 'Code PIN incorrect',
                        text: 'Le code saisi ne correspond pas à celui enregistré sur votre compte.',
                        confirmButtonText: 'Réessayer'
                    });
                    return; 
                }

            } else {
                // --- ÉTAPE : CRÉATION DU PREMIER PIN ---
                const { value: newPin, isDismissed } = await Swal.fire({
                    title: 'Nouveau Code PIN',
                    text: 'Choisissez 4 chiffres pour sécuriser vos collectes offline.',
                    input: 'password',
                    inputAttributes: { 
                        maxlength: 4, 
                        inputmode: 'numeric', 
                        pattern: '[0-9]*', // Aide certains navigateurs mobiles à afficher le pavé numérique
                        autocomplete: 'new-password'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Enregistrer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false, // Toujours conseillé pour les actions sensibles
                    preConfirm: (v) => {
                        if (!/^\d{4}$/.test(v)) {
                            return Swal.showValidationMessage('Veuillez saisir exactement 4 chiffres');
                        }
                        return v;
                    }
                });

                // Gestion de la suite
                if (isDismissed) {
                    console.log("L'agent a annulé le changement de PIN.");
                    return; // On arrête l'exécution ici
                }



                if (newPin) {
                     setUIState(true, "Initialisation...");
                    return finalizeLogin(newPin, data.agent, elements.matricule, data.token, false);
                }
            }

        } catch (error) {
            if (error.message.includes('CSRF') || error.message.includes('419')) {
                await refreshToken();
            }
            elements.alertBox.textContent = error.message;
            elements.alertBox.classList.remove('d-none');
            setUIState(false, "Se connecter");
        }
    });

    /**
     * Cette fonction n'est appelée QUE si le PIN est correct ou créé avec succès.
     */
    // On ajoute des valeurs par défaut aux nouveaux paramètres pour ne pas casser l'appel à 4 paramètres
    async function finalizeLogin(pin, authObj, matricule, token, isRecovery = false, existingHash = null) {
        const agentKey = `auth_v1_${matricule}`;
        
        // 1. Logique du Hash : soit on le calcule, soit on prend celui du serveur
        let finalHash = existingHash; 

        if (!isRecovery) {
            // Cas création : on calcule le hash à partir du nouveau PIN
            finalHash = CryptoJS.SHA256(pin + matricule + PIN_SALT).toString();

            // Sauvegarde sur le serveur uniquement si c'est une création
            try {
                await fetch("{{ route('pwa.pin.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ matricule: matricule, pin_hash: finalHash })
                });
            } catch (error) {
                console.warn("Échec synchro serveur, on continue en local.");
            }
        }

        // 2. Mise à jour de l'objet auth avec le bon hash
        authObj.pin_hash = finalHash;

        // 3. Stockage local identique à ta version qui marche
        localStorage.setItem(agentKey, JSON.stringify(authObj));
        localStorage.setItem('current_agent_matricule', matricule);
        localStorage.setItem('session_active', 'true');
        
        if (token) {
            localStorage.setItem('auth_token', token);
        }

        // 4. Redirection
        window.location.href = "/pwa/sync";
    }
        
    async function checkStatus(matricule){
        
        try {
            const check = await fetch(`/pwa/check-status/${matricule}`, {
                credentials: 'same-origin'
            });
            const status = await check.json();
            if (status.actif === false) {
                localStorage.removeItem(agentKey);
                throw new Error("Compte désactivé par l'admin.");
            }
        } catch (e) { /* On ignore l'erreur de check pour laisser passer l'offline */ }
    }
    // --- SERVICE WORKER & INSTALLATION ---
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(err => console.warn('SW Error', err));
    }


    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallPromotion();
    });

    async function showInstallPromotion() {
        const { isConfirmed } = await Swal.fire({
            title: 'Installation Requise',
            text: "Pour utiliser l'outil de collecte en mode sécurisé et hors-ligne, vous devez l'installer sur votre écran d'accueil.",
            icon: 'info',
            showCancelButton: false,
            confirmButtonText: 'Installer maintenant',
            confirmButtonColor: '#3085d6',
            allowOutsideClick: false, // Force l'utilisateur à interagir
            allowEscapeKey: false
        });

        if (isConfirmed && deferredPrompt) {
            // Afficher la boîte de dialogue d'installation système
            deferredPrompt.prompt();

            // Attendre le choix de l'utilisateur
            const { outcome } = await deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('L\'agent a installé la PWA');
                Swal.fire({
                    title: 'Parfait !',
                    text: 'L\'application est en cours d\'installation.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                // Si l'utilisateur refuse, on relance l'alerte pour "forcer" l'installation
                showInstallPromotion();
            }
            deferredPrompt = null;
        }
    }

    // Optionnel : Détecter si l'application est déjà lancée en mode installé (Standalone)
    window.addEventListener('appinstalled', () => {
        console.log('PWA installée avec succès');
        // On peut ici masquer des éléments spécifiques au navigateur
    });

</script>
</body>
</html>