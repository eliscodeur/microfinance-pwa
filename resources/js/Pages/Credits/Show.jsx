import { Inertia } from '@inertiajs/inertia';
import { Link, usePage } from '@inertiajs/inertia-react';
import { useEffect, useState } from 'react';
import AdminLayout from '../../Layouts/AdminLayout.jsx';
import BootstrapModal from '../../Components/BootstrapModal.jsx';
import { formatDateToFR } from '../../Utils/creditHelpers';

export default function Show({ credit }) {
    const { flash } = usePage().props;
    const [confirmOpen, setConfirmOpen] = useState(false);
    const payments = Array.isArray(credit.payments) ? credit.payments : credit.payments?.data ?? [];
    const pagination = Array.isArray(credit.payments) ? null : credit.payments ?? null;

    const [penalties, setPenalties] = useState(() =>
        payments.reduce((acc, payment) => ({
            ...acc,
            [payment.id]: payment.computed_penalty ?? payment.penalite ?? 0,
        }), {}),
    );
    const [paymentAmounts, setPaymentAmounts] = useState(() =>
        payments.reduce((acc, payment) => ({
            ...acc,
            [payment.id]: '',
        }), {}),
    );

    useEffect(() => {
        setPenalties(
            payments.reduce((acc, payment) => ({
                ...acc,
                [payment.id]: payment.computed_penalty ?? payment.penalite ?? 0,
            }), {}),
        );
        setPaymentAmounts(
            payments.reduce((acc, payment) => ({
                ...acc,
                [payment.id]: '',
            }), {}),
        );
    }, [credit.payments]);

    const formatCurrency = value =>
        new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XAF',
            maximumFractionDigits: 0,
        }).format(value);

    const paymentClass = status => {
        switch (status) {
            case 'paid':
                return 'badge bg-success';
            case 'late':
                return 'badge bg-danger';
            case 'pending':
                return 'badge bg-warning text-dark';
            case 'partiel':
                return 'badge bg-info text-dark';
            default:
                return 'badge bg-secondary';
        }
    };

    const creditStatusLabel = status => {
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

    const paymentStatusLabel = status => {
        switch (status) {
            case 'paid':
                return 'Payé';
            case 'late':
                return 'En retard';
            case 'pending':
                return 'En attente';
            case 'partiel':
                return 'Partiel';
            default:
                return 'Inconnu';
        }
    };

    const canPayInstallment = payment => {
        if (payment.status === 'paid') {
            return false;
        }
        if (payment.can_pay !== undefined) {
            return payment.can_pay;
        }

        return payments
            .filter(p => p.echeance < payment.echeance)
            .every(p => p.status === 'paid');
    };

    const approve = () => {
        setConfirmOpen(true);
    };

    const confirmApprove = () => {
        setConfirmOpen(false);
        Inertia.post(`/admin/credits/${credit.id}/approve`);
    };

    const savePenalty = (payment) => {
        const amount = Number(penalties[payment.id] ?? payment.computed_penalty ?? 0);

        if (Number.isNaN(amount) || amount < 0) {
            return;
        }

        Inertia.patch(`/admin/credits/${credit.id}/payments/${payment.id}`, {
            penalite: amount,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setPenalties(prev => ({
                    ...prev,
                    [payment.id]: amount,
                }));
            },
        });
    };

    const savePayment = (payment) => {
        const amount = Number(paymentAmounts[payment.id]);

        if (Number.isNaN(amount) || amount <= 0) {
            return;
        }

        Inertia.patch(`/admin/credits/${credit.id}/payments/${payment.id}`, {
            montant_paye: amount,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setPaymentAmounts(prev => ({
                    ...prev,
                    [payment.id]: '',
                }));
            },
        });
    };

    const closeConfirm = () => {
        setConfirmOpen(false);
    };

    return (
        <AdminLayout>
            <div>
                {flash.success && (
                    <div className="alert alert-success" role="alert">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="alert alert-danger" role="alert">
                        {flash.error}
                    </div>
                )}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 className="h3">Crédit #{credit.id}</h1>
                        <p className="text-muted mb-0">
                            Détail du dossier de crédit et échéancier.
                        </p>
                    </div>
                    <div>
                        <Link href="/admin/credits" className="btn btn-outline-secondary me-2">
                            Retour
                        </Link>
                        {credit.statut === 'pending' && (
                            <button className="btn btn-success" onClick={approve}>
                                Approuver
                            </button>
                        )}
                    </div>
                </div>

                <BootstrapModal
                    show={confirmOpen}
                    title="Confirmation"
                    body="Approuver ce crédit et activer l’échéancier ?"
                    onConfirm={confirmApprove}
                    onClose={closeConfirm}
                    confirmText="Oui, approuver"
                    cancelText="Annuler"
                    confirmVariant="success"
                />

                <div className="row g-3 mb-4">
                    <div className="col-md-4">
                        <div className="card shadow-sm p-3">
                            <h5 className="mb-3">Informations</h5>
                            <p>
                                <strong>Client :</strong> {credit.client.nom} {credit.client.prenom}
                            </p>
                            <p>
                                <strong>Statut :</strong> {creditStatusLabel(credit.statut)}
                            </p>
                            <p>
                                <strong>Montant demandé :</strong> {formatCurrency(credit.montant_demande)}
                            </p>
                            <p>
                                <strong>Taux appliqué :</strong> {credit.taux}%
                            </p>
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="card shadow-sm p-3">
                            <h5 className="mb-3">Conditions</h5>
                            <p>
                                <strong>Type :</strong> {credit.type}
                            </p>
                            <p>
                                <strong>Mode :</strong> {credit.mode}
                            </p>
                            <p>
                                <strong>Périodicité :</strong> {credit.periodicite}
                            </p>
                            <p>
                                <strong>Échéances :</strong> {credit.nombre_echeances}
                            </p>
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="card shadow-sm p-3">
                            <h5 className="mb-3">Chiffres clés</h5>
                            <p>
                                <strong>Intérêt total :</strong> {formatCurrency(credit.interet_total)}
                            </p>
                            <p>
                                <strong>Pénalités totales :</strong> {formatCurrency(credit.penalty_amount ?? 0)}
                            </p>
                            <p>
                                <strong>Montant total dû :</strong>{' '}
                                {formatCurrency(
                                    Number(credit.montant_accorde) + Number(credit.interet_total) + Number(credit.penalty_amount ?? 0),
                                )}
                            </p>
                            <p>
                                <strong>Montant remboursé :</strong> {formatCurrency(credit.montant_rembourse)}
                            </p>
                        </div>
                    </div>
                </div>

                {credit.emergency_withdrawal_summary?.length > 0 && (
                    <>
                        <div className="alert alert-warning">
                            <strong>Prélèvement automatique appliqué :</strong> des sommes ont été prélevées sur l'épargne disponible pour couvrir une ou plusieurs échéances en défaut.
                        </div>
                        <div className="card shadow-sm mb-4 border-danger">
                            <div className="card-header bg-white text-danger">
                                <strong>Prélèvements de secours automatiques</strong>
                            </div>
                            <div className="card-body">
                                <p className="mb-3 text-muted">
                                    Les montants suivants ont été retirés automatiquement et affectés aux paiements en défaut.
                                </p>
                                <ul className="list-group list-group-flush">
                                    {credit.emergency_withdrawal_summary.map((item, index) => (
                                        <li key={index} className="list-group-item px-0">
                                            <strong>Échéance #{item.echeance} :</strong> {formatCurrency(item.amount_withdrawn)} prélevés, dont {formatCurrency(item.amount_applied)} appliqués au paiement.
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </>
                )}

                <div className="card shadow-sm">
                    <div className="card-header bg-white">
                        <strong>Échéancier</strong>
                    </div>
                    <div className="card-body p-0">
                        <table className="table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Principal</th>
                                    <th>Intérêts</th>
                                    <th>Total</th>
                                    <th>Pénalité</th>
                                    <th>Payé</th>
                                    <th>Reste</th>
                                    <th>Paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payments.map(payment => {
                                    const totalDue = Number(payment.montant_total) + Number(payment.penalite ?? 0);
                                    const paidAmount = Number(payment.montant_paye ?? 0);
                                    const remaining = Math.max(0, totalDue - paidAmount);

                                    return (
                                        <tr key={payment.id}>
                                            <td>{payment.echeance}</td>
                                            <td>{formatDateToFR(payment.due_date)}</td>
                                            <td>{formatCurrency(payment.montant_principal)}</td>
                                            <td>{formatCurrency(payment.montant_interets)}</td>
                                            <td>{formatCurrency(payment.montant_total)}</td>
                                            <td>
                                                {payment.status !== 'paid' ? (
                                                    <div className="input-group input-group-sm">
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            className="form-control"
                                                            value={penalties[payment.id] ?? payment.computed_penalty ?? 0}
                                                            onChange={e =>
                                                                setPenalties(prev => ({
                                                                    ...prev,
                                                                    [payment.id]: e.target.value,
                                                                }))
                                                            }
                                                        />
                                                        <button
                                                            type="button"
                                                            className="btn btn-outline-secondary"
                                                            onClick={() => savePenalty(payment)}
                                                        >
                                                            Enregistrer
                                                        </button>
                                                    </div>
                                                ) : (
                                                    formatCurrency(payment.computed_penalty ?? payment.penalite ?? 0)
                                                )}
                                            </td>
                                            <td>{formatCurrency(paidAmount)}</td>
                                            <td>{formatCurrency(remaining)}</td>
                                            <td>
                                                {payment.status !== 'paid' ? (
                                                    <div className="d-flex gap-2 align-items-center">
                                                        <div className="input-group input-group-sm">
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                className="form-control"
                                                                placeholder="Montant"
                                                                value={paymentAmounts[payment.id] ?? ''}
                                                                onChange={e =>
                                                                    setPaymentAmounts(prev => ({
                                                                        ...prev,
                                                                        [payment.id]: e.target.value,
                                                                    }))
                                                                }
                                                                disabled={!canPayInstallment(payment)}
                                                            />
                                                            <button
                                                                type="button"
                                                                className="btn btn-outline-secondary"
                                                                onClick={() => savePayment(payment)}
                                                                disabled={!canPayInstallment(payment)}
                                                            >
                                                                Payer
                                                            </button>
                                                        </div>
                                                        <span className={paymentClass(payment.display_status ?? payment.status)}>
                                                            {paymentStatusLabel(payment.display_status ?? payment.status)}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className={paymentClass(payment.display_status ?? payment.status)}>
                                                        {paymentStatusLabel(payment.display_status ?? payment.status)}
                                                    </span>
                                                )}
                                                {!canPayInstallment(payment) && payment.status !== 'paid' && (
                                                    <div className="small text-muted mt-1">
                                                        Paiement bloqué tant que l’échéance précédente n’est pas réglée.
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                                {payments.length === 0 && (
                                    <tr>
                                        <td colSpan="9" className="text-center py-4">
                                            Aucun échéancier disponible.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {pagination && (
                    <div className="mt-3 d-flex justify-content-end gap-2">
                        <button
                            className="btn btn-outline-secondary"
                            onClick={() => Inertia.visit(pagination.prev_page_url)}
                            disabled={!pagination.prev_page_url}
                        >
                            Précédent
                        </button>
                        <button
                            className="btn btn-outline-secondary"
                            onClick={() => Inertia.visit(pagination.next_page_url)}
                            disabled={!pagination.next_page_url}
                        >
                            Suivant
                        </button>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
