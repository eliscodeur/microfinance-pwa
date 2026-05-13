<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Connexion Agent - NANA Eco Consulting</title>
    
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
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
    <div id="install-banner" class="shadow-sm">
        <i class="bi bi-cloud-arrow-down-fill me-2"></i>
        <span>Accéder au mode hors-ligne</span>
        <button id="btn-install-now" class="btn btn-light btn-sm fw-bold ms-3" style="border-radius: 8px;">Installer</button>
    </div>

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

    document.getElementById('pwa-login-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const elements = {
            matricule: document.getElementById('username').value.trim(),
            password: document.getElementById('password').value,
            alertBox: document.getElementById('error-alert'),
            btn: document.getElementById('btn-submit'),
            btnText: document.getElementById('btn-text'),
            spinner: document.getElementById('btn-spinner'),
            icon: document.getElementById('btn-icon')
        };

        const setUIState = (loading, text = "Vérification...") => {
            elements.btn.disabled = loading;
            elements.btnText.innerText = text;
            elements.spinner.classList.toggle('d-none', !loading);
            elements.icon.classList.toggle('d-none', loading);
            if (loading) elements.alertBox.classList.add('d-none');
        };

        try {
            // Vérification de la présence de CryptoJS
            if (typeof CryptoJS === 'undefined') {
                throw new Error("Erreur système : Bibliothèque de sécurité manquante.");
            }

            const agentKey = `auth_v1_${elements.matricule}`;
            const localAuth = JSON.parse(localStorage.getItem(agentKey));

            // -------------------------------------------------------
            // CAS 1 : CONNEXION OFFLINE (AGENT DÉJA ENREGISTRÉ)
            // -------------------------------------------------------
            if (localAuth && localAuth.pin_hash) {
                const inputHash = CryptoJS.SHA256(elements.password + elements.matricule + PIN_SALT).toString();

                if (inputHash === localAuth.pin_hash) {
                    setUIState(true, "Accès local...");
                    
                    // Vérification furtive du statut si on a du réseau
                    if (navigator.onLine) {
                        try {
                            const check = await fetch(`/api/agent/check-status/${elements.matricule}`, {
                                credentials: 'same-origin'
                            });
                            const status = await check.json();
                            if (status.is_active === false) {
                                localStorage.removeItem(agentKey);
                                throw new Error("Compte désactivé par l'admin.");
                            }
                        } catch (e) { /* On ignore l'erreur de check pour laisser passer l'offline */ }
                    }

                    localStorage.setItem('current_agent_matricule', elements.matricule);
                    localStorage.setItem('session_active', true);
                    window.location.href = "/pwa/dashboard";
                    return;
                } else {
                    throw new Error("Code PIN incorrect.");
                }
            }

            // -------------------------------------------------------
            // CAS 2 : PREMIÈRE CONNEXION (ONLINE OBLIGATOIRE)
            // -------------------------------------------------------
            if (!navigator.onLine) {
                throw new Error("⚠️ Première connexion impossible sans internet.");
            }

            setUIState(true, "Vérification serveur...");

            const response = await fetch("{{ route('agent.login.submit') }}", {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ username: elements.matricule, password: elements.password })
            });

            const data = await response.json();

            if (!response.ok) throw new Error(data.message || "Identifiants invalides.");
            if (data.agent.actif === false) throw new Error("Compte désactivé.");
            if (data.agent.sync === 0) throw new Error("Synchronisation non autorisée.");

            // Préparation des données pour le PIN
            const tempAuth = {
                id: data.agent.id,
                nom: data.agent.nom,
                matricule: elements.matricule,
                photo: data.agent.photo || ''
            };

            setUIState(false, "Se connecter");
            
            // Affichage du modal PIN
            if (pinModalInstance) {
                pinModalInstance.show();
            } else {
                // Fallback si Bootstrap a un souci
                const pin = prompt("Créez votre code PIN à 4 chiffres :");
                if(pin) finalizeLogin(pin, tempAuth, elements.matricule, data.token);
            }

            // Gestion du clic sur confirmer PIN
            document.getElementById('confirm-pin-btn').onclick = async () => {
                const pinSaisi = document.getElementById('new-pin').value;
                if (pinSaisi.length < 4) {
                    alert("4 chiffres minimum.");
                    return;
                }
                finalizeLogin(pinSaisi, tempAuth, elements.matricule, data.token);
            };

        } catch (error) {
            console.error("Erreur Auth:", error);
            elements.alertBox.textContent = error.message;
            elements.alertBox.classList.remove('d-none');
            setUIState(false, "Se connecter");
        }
    });

    
    async function finalizeLogin(pin, authObj, matricule, token) {
        // Clé unique par agent pour éviter d'écraser les données d'un collègue
        const agentKey = `auth_v1_${matricule}`;
        
        // 1. Calcul du hash (le matricule est inclus pour rendre le hash unique par agent)
        const generatedHash = CryptoJS.SHA256(pin + matricule + PIN_SALT).toString();
        authObj.pin_hash = generatedHash;

        try {
            // 2. Envoi au serveur sur la route protégée du PwaController
            // Utilisation de la route nommée via Blade pour plus de sécurité
            const response = await fetch("{{ route('pwa.pin.update') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    // On peut envoyer le matricule pour vérification, 
                    // mais le serveur identifiera l'agent via auth()->user()
                    matricule: matricule, 
                    pin_hash: generatedHash
                })
            });

            if (!response.ok) {
                console.warn("Stockage serveur échoué, priorité au mode local.");
            }
        } catch (error) {
            console.error("Erreur réseau (possible mode offline) :", error);
        }

        // 3. Stockage local isolé par agent
        // On sauvegarde l'objet auth complet (nom, photo, hash) pour cet agent précis
        localStorage.setItem(agentKey, JSON.stringify(authObj));
        
        // On définit l'agent qui vient de se connecter comme l'utilisateur actif
        localStorage.setItem('current_agent_matricule', matricule);
        localStorage.setItem('session_active', 'true');
        
        if (token) {
            localStorage.setItem('auth_token', token);
        }

        // 4. Redirection vers la synchronisation
        window.location.href = "/pwa/sync";
    }

    // --- SERVICE WORKER & INSTALLATION ---
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(err => console.warn('SW Error', err));
    }

    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const banner = document.getElementById('install-banner');
        if (banner) banner.classList.remove('d-none'); // Utilise d-none de bootstrap
    });

    const installBtn = document.getElementById('btn-install-now');
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                document.getElementById('install-banner').classList.add('d-none');
            }
            deferredPrompt = null;
        });
    }
</script>
</body>
</html>