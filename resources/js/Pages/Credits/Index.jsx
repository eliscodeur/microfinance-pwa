import { Inertia } from '@inertiajs/inertia';
import { Link } from '@inertiajs/inertia-react';
import AdminLayout from '../../Layouts/AdminLayout.jsx';

export default function Index({ credits }) {
    const formatCurrency = value =>
        new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XAF',
            maximumFractionDigits: 0,
        }).format(value);

    const statusClass = status => {
        switch (status) {
            case 'approved':
                return 'badge bg-success';
            case 'active':
                return 'badge bg-primary';
            case 'pending':
                return 'badge bg-warning text-dark';
            case 'in_arrears':
                return 'badge bg-danger';
            case 'closed':
                return 'badge bg-secondary';
            case 'rejected':
                return 'badge bg-dark';
            default:
                return 'badge bg-info';
        }
    };

    const statusLabel = status => {
        switch (status) {
            case 'approved':
                return 'Approuvé';
            case 'active':
                return 'Actif';
            case 'pending':
                return 'En attente';
            case 'in_arrears':
                return 'En retard';
            case 'closed':
                return 'Clôturé';
            case 'rejected':
                return 'Rejeté';
            default:
                return 'Inconnu';
        }
    };

    const prevPage = () => {
        if (credits.prev_page_url) Inertia.visit(credits.prev_page_url);
    };

    const nextPage = () => {
        if (credits.next_page_url) Inertia.visit(credits.next_page_url);
    };

    return (
        <AdminLayout>
            <div>
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 className="h3">Gestion des crédits</h1>
                        <p className="text-muted mb-0">
                            Liste des demandes et crédits clients.
                        </p>
                    </div>
                    <Link href="/admin/credits/create" className="btn btn-primary">
                        Nouvelle demande
                    </Link>
                </div>

                <div className="card shadow-sm">
                    <div className="card-body p-0">
                        <table className="table table-hover mb-0">
                            <thead className="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Type</th>
                                    <th>Périodicité</th>
                                    <th>Statut</th>
                                    <th>Échéances</th>
                                    <th>Créé le</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {credits.data.map(credit => (
                                    <tr key={credit.id}>
                                        <td>{credit.id}</td>
                                        <td>
                                            {credit.client.nom} {credit.client.prenom}
                                        </td>
                                        <td>{formatCurrency(credit.montant_demande)}</td>
                                        <td>{credit.type}</td>
                                        <td>{credit.periodicite}</td>
                                        <td>
                                            <span className={statusClass(credit.statut)}>
                                                {statusLabel(credit.statut)}
                                            </span>
                                        </td>
                                        <td>{credit.nombre_echeances}</td>
                                        <td>{credit.created_at}</td>
                                        <td>
                                            <Link
                                                href={`/admin/credits/${credit.id}`}
                                                className="btn btn-sm btn-outline-primary"
                                            >
                                                Voir
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {credits.data.length === 0 && (
                                    <tr>
                                        <td colSpan="9" className="text-center py-4">
                                            Aucun crédit trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="mt-3 d-flex justify-content-end">
                    <button
                        className="btn btn-outline-secondary"
                        onClick={prevPage}
                        disabled={!credits.prev_page_url}
                    >
                        Précédent
                    </button>
                    <button
                        className="btn btn-outline-secondary ms-2"
                        onClick={nextPage}
                        disabled={!credits.next_page_url}
                    >
                        Suivant
                    </button>
                </div>
            </div>
        </AdminLayout>
    );
}
