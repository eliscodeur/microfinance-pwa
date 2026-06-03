import { Inertia } from '@inertiajs/inertia';
import { Link } from '@inertiajs/inertia-react';
import AdminLayout from '../../Layouts/AdminLayout.jsx';

// Regroupement des configurations de statuts pour une maintenance plus propre
const STATUS_CONFIG = {
    pending: { label: 'En attente', class: 'badge bg-warning text-dark' },
    approved: { label: 'Approuvé', class: 'badge bg-success' },
    active: { label: 'Actif', class: 'badge bg-primary' },
    in_arrears: { label: 'En retard', class: 'badge bg-danger' },
    solder: { label: 'Soldé', class: 'badge bg-success' },
    closed: { label: 'Clôturé', class: 'badge bg-secondary' },
    rejected: { label: 'Rejeté', class: 'badge bg-dark' },
};

const TYPE_LABELS = {
    compte: 'Sur compte',
    quinzaine: 'Quinzaine',
    mensuel: 'Mensuel',
};

const PERIODICITE_LABELS = {
    quinzaine: 'Quinzaine',
    mensuelle: 'Mensuelle',
};

export default function Index({ credits }) {
    
    // Formatage monétaire strict (XAF)
    const formatCurrency = value =>
        new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XAF',
            maximumFractionDigits: 0,
        }).format(value);

    // Formatage des dates du format ISO/DB vers le format FR
    const formatDate = dateString => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return isNaN(date.getTime()) 
            ? dateString 
            : date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };

    const getStatusBadge = status => {
        const config = STATUS_CONFIG[status] || { label: 'Inconnu', class: 'badge bg-info' };
        return <span className={config.class}>{config.label}</span>;
    };

    // Actions de pagination via le nouveau router Inertia
    const handlePagination = url => {
        if (url) {
            router.visit(url, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout>
            <div>
                {/* Header Section */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 className="h3 mb-1 fw-bold text-dark">Gestion des crédits</h1>
                        <p className="text-muted mb-0">
                            Liste des demandes et suivi des encours crédits clients.
                        </p>
                    </div>
                    <Link href="/admin/credits/create" className="btn btn-primary shadow-sm d-flex align-items-center gap-2">
                        <i className="bi bi-plus-lg"></i> Nouvelle demande
                    </Link>
                </div>

                {/* Table Card */}
                <div className="card shadow-sm border-0">
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover align-middle mb-0">
                                <thead className="table-light text-uppercase fs-7 text-muted">
                                    <tr>
                                        <th className="ps-4" style={{ width: '80px' }}>#</th>
                                        <th>Client</th>
                                        <th>Montant</th>
                                        <th>Type</th>
                                        <th>Périodicité</th>
                                        <th>Statut</th>
                                        <th>Échéances</th>
                                        <th>Créé le</th>
                                        <th className="pe-4 text-end" style={{ width: '100px' }}>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {credits.data.map(credit => (
                                        <tr key={credit.id}>
                                            <td className="ps-4 fw-medium text-secondary">#{credit.id}</td>
                                            <td className="fw-semibold text-dark">
                                                {credit.client ? `${credit.client.nom} ${credit.client.prenom}` : 'Client inconnu'}
                                            </td>
                                            <td className="fw-bold text-dark">
                                                {formatCurrency(credit.montant_demande)}
                                            </td>
                                            <td>
                                                <span className="text-capitalize">
                                                    {TYPE_LABELS[credit.type] || credit.type}
                                                </span>
                                            </td>
                                            <td>
                                                <span className="text-capitalize">
                                                    {PERIODICITE_LABELS[credit.periodicite] || credit.periodicite}
                                                </span>
                                            </td>
                                            <td>{getStatusBadge(credit.statut)}</td>
                                            <td>
                                                <span className="badge bg-light text-dark border">
                                                    {credit.nombre_echeances} échéances
                                                </span>
                                            </td>
                                            <td className="text-muted">{formatDate(credit.created_at)}</td>
                                            <td className="pe-4 text-end">
                                                <Link
                                                    href={`/admin/credits/${credit.id}`}
                                                    className="btn btn-sm btn-outline-primary px-3 rounded-pill"
                                                >
                                                    Voir
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}

                                    {credits.data.length === 0 && (
                                        <tr>
                                            <td colSpan="9" className="text-center py-5 text-muted">
                                                <div className="py-3">
                                                    <i className="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                                    Aucun dossier de crédit trouvé dans le système.
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {/* Section Pagination Professionnelle */}
                {credits.links && credits.links.length > 3 && (
                    <div className="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
                        <div className="text-muted small">
                            Affichage de {credits.from || 0} à {credits.to || 0} sur {credits.total} demandes
                        </div>
                        <nav>
                            <ul className="pagination mb-0 pagination-sm">
                                {credits.links.map((link, index) => (
                                    <li 
                                        key={index} 
                                        className={`page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`}
                                    >
                                        <button
                                            type="button"
                                            className="page-link"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            onClick={() => handlePagination(link.url)}
                                            disabled={!link.url}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}