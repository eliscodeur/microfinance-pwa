<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @inertiaHead
    <title>Nana Eco Consulting - Admin</title>
    
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192x192.png') }}">
    
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">

    <style>
        .swal2-popup { border-radius: 1rem !important; }
        .swal2-styled { border-radius: 0.5rem !important; }
        :root {
            --sidebar-width: 260px;
            --sidebar-mini-width: 75px;
            --topbar-height: 70px;
            --primary-bg: #1f2432;
            --secondary-bg: #2a2f3f;
            --accent-color: #638afd;
        }

        body { 
            background-color: #f7f9fc; 
            margin: 0; 
            overflow-x: hidden; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }

        /* TOPBAR */
        .admin-topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #eef2f7;
            z-index: 1060;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }

        .hamburger-btn {
            background: #f1f3f9;
            border: none;
            width: 45px; height: 45px;
            border-radius: 10px;
            color: var(--primary-bg);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }

        /* CONTENT AREA */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        .content.expanded { margin-left: var(--sidebar-mini-width); }

        @media (max-width: 991px) {
            .content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>

    <header class="admin-topbar justify-content-between shadow-sm px-3">
        <div class="d-flex align-items-center">
            <button class="hamburger-btn me-3" id="hamburgerBtn">
                <i class="bi bi-list fs-4"></i>
            </button>
            <img src="{{ asset('icons/icon-192x192.png') }}" alt="Logo" style="height: 35px;" class="me-2">
            <span class="fw-bold d-none d-sm-inline">NANA ECO CONSULTING</span>
        </div>
        
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="text-end me-2 d-none d-md-block">
                        <div class="fw-bold mb-0 lh-1" style="font-size: 0.9rem;">{{ Auth::user() ? Auth::user()->name : 'Admin' }}</div>
                        <small class="text-muted" style="font-size: 0.75rem;">Administrateur</small>
                    </div>
                    <div class="avatar-circle bg-dark text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 35px; height: 35px;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="profileDropdown">
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="/admin/profile">
                            <i class="bi bi-person me-2"></i> Mon Profil
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" id="logout-form">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex align-items-center text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Déconnexion
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    @include('admin.layouts.partials.sidebar')

    <main class="content" id="mainContent">
        <div class="container-fluid p-4">
            @inertia
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    
    <script>
        const btn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebarNav');
        const content = document.getElementById('mainContent');

        if (btn) {
            btn.addEventListener('click', () => {
                if (window.innerWidth >= 992) {
                    if (sidebar) sidebar.classList.toggle('mini');
                    if (content) content.classList.toggle('expanded');
                } else {
                    if (sidebar) sidebar.classList.toggle('open');
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && sidebar && btn && !sidebar.contains(e.target) && !btn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // On cible tous les liens présents dans la sidebar
            const sidebarLinks = document.querySelectorAll('#sidebarNav a, .admin-sidebar a');
            
            sidebarLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    // Si on est sur une page gérée par Inertia
                    if (window.Inertia || (window.modules && window.modules.Inertia)) {
                        const href = link.getAttribute('href');
                        
                        // Si le lien est interne et n'est pas une déconnexion
                        if (href && href.startsWith('/') && !href.includes('logout') && !href.startsWith('#')) {
                            e.preventDefault(); // Bloque le rechargement de page classique
                            
                            // On appelle Inertia pour charger la page de manière fluide
                            if (window.Inertia) {
                                window.Inertia.visit(href);
                            } else if (window.router) { // Pour les versions récentes d'Inertia (@inertiajs/react)
                                window.router.visit(href);
                            } else {
                                // Sécurité au cas où l'objet global est attaché différemment
                                e.defaultPrevented = false;
                                window.location.href = href;
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>