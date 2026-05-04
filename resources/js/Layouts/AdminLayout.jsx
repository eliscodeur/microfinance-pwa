import { Link } from '@inertiajs/inertia-react';

export default function AdminLayout({ children }) {
    return (
        <div>
            <nav className="navbar navbar-expand-lg navbar-dark bg-dark">
                <div className="container-fluid px-4">
                    <Link href="/admin/dashboard" className="navbar-brand">
                        NANA ECO CONSULTING
                    </Link>
                    <button
                        className="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#navbarNav"
                        aria-controls="navbarNav"
                        aria-expanded="false"
                        aria-label="Toggle navigation"
                    >
                        <span className="navbar-toggler-icon" />
                    </button>
                    <div className="collapse navbar-collapse" id="navbarNav">
                        <ul className="navbar-nav ms-auto">
                            <li className="nav-item">
                                <Link href="/admin/clients" className="nav-link">
                                    Clients
                                </Link>
                            </li>
                            <li className="nav-item">
                                <Link href="/admin/carnets" className="nav-link">
                                    Carnets
                                </Link>
                            </li>
                            <li className="nav-item">
                                <Link href="/admin/credits" className="nav-link">
                                    Crédits
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <main className="container mt-4">
                {children}
            </main>
        </div>
    );
}
