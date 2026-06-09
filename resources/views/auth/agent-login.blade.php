<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Connexion Agent - NANA Eco Consulting</title>
    
    <link rel="manifest" href="/pwa/manifest.json">
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

    <div class="container my-auto" id="my-app-container">
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
                                       placeholder="Ex: NEC-00001" 
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
</body>
</html>
<script>
    // Force l'état initial dans l'historique dès le chargement de la page de connexion
    window.onload = function () {
        window.history.pushState(null, null, window.location.href);
        
        window.onpopstate = function () {
            // Si l'agent tente de reculer, on le pousse de force un coup en avant
            window.history.go(1);
        };
    };
</script>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(reg => {
            reg.addEventListener('updatefound', () => {
                const newWorker = reg.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // Optionnel : afficher un message "Mise à jour disponible"
                        console.log("Nouvelle version dispo ! Rafraîchissez pour appliquer.");
                    }
                });
            });
        });
    }
</script>
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

    // Fonction pour afficher/masquer le mot de passe (Rattachée à window pour rester globale)
    window.togglePassword = function() {
        const passInput = document.getElementById('password');
        const icon = document.getElementById('toggle-icon');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            passInput.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    };

    async function refreshToken() {
        try {
            const response = await fetch('/refresh-csrf');
            const data = await response.json();
            let meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                meta.setAttribute('content', data.token);
            }
            return data.token;
        } catch (e) {
            console.error("Échec du rafraîchissement CSRF", e);
        }
    }

    // Événement unique de soumission du formulaire
    const loginForm = document.getElementById('pwa-login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const elements = {
                matricule: document.getElementById('username').value.trim(),
                password: document.getElementById('password').value,
                alertBox: document.getElementById('error-alert'),
                btn: document.getElementById('btn-submit'),
                btnText: document.getElementById('btn-text'),
                spinner: document.getElementById('btn-spinner'),
                btnIcon: document.getElementById('btn-icon')
            };

            const setUIState = (loading, text = "Vérification...") => {
                if (elements.btn) elements.btn.disabled = loading;
                if (elements.btnText) elements.btnText.innerText = text;
                if (elements.spinner) elements.spinner.classList.toggle('d-none', !loading);
                if (elements.btnIcon) {
                    if (loading) elements.btnIcon.classList.add('d-none');
                    else elements.btnIcon.classList.remove('d-none');
                }
                if (loading && elements.alertBox) elements.alertBox.classList.add('d-none');
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
                           checkStatus(elements.matricule);
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
                
                const serverPinHash = data.agent.pin_hash; 
                
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

                    if (!pinSaisi) return; 

                    const inputHash = CryptoJS.SHA256(pinSaisi + elements.matricule + PIN_SALT).toString();

                    if (inputHash === serverPinHash) {
                        setUIState(true, "Initialisation...");
                        return finalizeLogin(null, data.agent, elements.matricule, data.token, true, serverPinHash);
                    } else {
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
                            pattern: '[0-9]*', 
                            autocomplete: 'new-password'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Enregistrer',
                        cancelButtonText: 'Annuler',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false, 
                        preConfirm: (v) => {
                            if (!/^\d{4}$/.test(v)) {
                                return Swal.showValidationMessage('Veuillez saisir exactement 4 chiffres');
                            }
                            return v;
                        }
                    });

                    if (isDismissed) {
                        console.log("L'agent a annulé le changement de PIN.");
                        return; 
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
                if (elements.alertBox) {
                    elements.alertBox.textContent = error.message;
                    elements.alertBox.classList.remove('d-none');
                }
                setUIState(false, "Se connecter");
            }
        });
    }

    async function finalizeLogin(pin, authObj, matricule, token, isRecovery = false, existingHash = null) {
        const agentKey = `auth_v1_${matricule}`;
        let finalHash = existingHash; 

        if (!isRecovery) {
            finalHash = CryptoJS.SHA256(pin + matricule + PIN_SALT).toString();

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

        authObj.pin_hash = finalHash;

        localStorage.setItem(agentKey, JSON.stringify(authObj));
        localStorage.setItem('current_agent_matricule', matricule);
        localStorage.setItem('session_active', 'true');
        
        if (token) {
            localStorage.setItem('auth_token', token);
        }
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ action: 'cachePrivatePages' });
        }
        window.location.href = "/pwa/sync";
    }
        
    async function checkStatus(matricule){
        try {
            const check = await fetch(`/pwa/check-status/${matricule}`, {
                credentials: 'same-origin'
            });
            const status = await check.json();
            if (status.actif === false) {
                const agentKey = `auth_v1_${matricule}`;
                localStorage.removeItem(agentKey);
                throw new Error("Compte désactivé par l'admin.");
            }
        } catch (e) { }
    }

    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallPromotion();
    });

    let isPromoting = false; // Flag global
    async function showInstallPromotion() {
        if (isPromoting) return; // Empêche l'affichage multiple
        isPromoting = true;
        const { isConfirmed } = await Swal.fire({
            title: 'Installation Requise',
            text: "Pour utiliser l'outil de collecte en mode sécurisé et hors-ligne, vous devez l'installer sur votre écran d'accueil.",
            icon: 'info',
            showCancelButton: false,
            confirmButtonText: 'Installer maintenant',
            confirmButtonColor: '#3085d6',
            allowOutsideClick: false, 
            allowEscapeKey: false
        });

        if (isConfirmed && deferredPrompt) {
            // 1. On sauvegarde l'instance localement et on VIDE immédiatement la variable globale
            // Cela empêche toute autre fonction ou événement de ré-utiliser le même prompt
            const promptEvent = deferredPrompt;
            deferredPrompt = null; 

            // 2. Déclencher le prompt natif
            promptEvent.prompt();

            // 3. Attendre le choix de l'agent
            const { outcome } = await promptEvent.userChoice;
            
            if (outcome === 'accepted') {
                console.log('L\'agent a installé la PWA');
                
                // SweetAlert de succès indiquant la marche à suivre
                Swal.fire({
                    title: 'Parfait !',
                    html: `L'application est installée.<br><br>
                        <div class="alert alert-warning text-start small mb-0">
                            <strong>Important :</strong> Vous pouvez maintenant <strong>fermer ce navigateur</strong> et ouvrir l'application directement depuis l'icône créée sur votre écran d'accueil.
                        </div>`,
                    icon: 'success',
                    showConfirmButton: true,
                    confirmButtonText: 'Compris',
                    allowOutsideClick: false
                });
            } else {
                // Si l'agent a refusé dans la boîte native du navigateur, on remet le prompt
                // et on relance l'alerte pour le forcer à accepter
                deferredPrompt = promptEvent;
                showInstallPromotion();
            }
        }
        isPromoting = false;
    }

    window.addEventListener('appinstalled', (evt) => {
        // L'application a été installée avec succès !
        
        deferredPrompt = null;
        // Option 1 : Modifier le DOM pour afficher un message d'instruction clair
        const container = document.getElementById('my-app-container'); // Assurez-vous d'avoir un conteneur principal avec cet ID
        if (container) {
            container.innerHTML = `
                <div class="text-center py-5 px-3">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Installation réussie !</h3>
                    <p class="text-muted my-3">
                        L'application est maintenant disponible sur votre écran d'accueil.
                    </p>
                    <div class="alert alert-info small shadow-sm" style="border-radius: 15px;">
                        <strong>Action requise :</strong> Vous pouvez maintenant <strong>quitter et fermer cet onglet</strong> de votre navigateur, puis lancer l'application directement depuis l'icône installée sur votre téléphone.
                    </div>
                </div>
            `;
        }
    });
</script>
</body>
</html>