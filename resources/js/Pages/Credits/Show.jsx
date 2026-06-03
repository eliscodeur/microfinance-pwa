import { Inertia } from '@inertiajs/inertia';
import { Link, usePage } from '@inertiajs/inertia-react';
import { useEffect, useState, useMemo } from 'react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import AdminLayout from '../../Layouts/AdminLayout.jsx';
import { formatDateToFR } from '../../Utils/creditHelpers';

const MySwal = withReactContent(Swal);

// Nomenclature institutionnelle — Tons neutres et professionnels
const CREDIT_STATUS_CONFIG = {
    pending: { label: 'En attente d\'instruction', class: 'badge bg-light text-secondary border border-secondary-subtle' },
    approved: { label: 'Approuvé (Non décaissé)', class: 'badge bg-light text-info border border-info-subtle' },
    active: { label: 'Sain / En cours', class: 'badge bg-light text-success border border-success-subtle' },
    in_arrears: { label: 'En Souffrance / Impayé', class: 'badge bg-light text-danger border border-danger-subtle' },
    solder: { label: 'Soldé', class: 'badge bg-light text-dark border' },
    solde: { label: 'Soldé', class: 'badge bg-light text-dark border' },
    closed: { label: 'Clôturé', class: 'badge bg-light text-muted border' },
    rejected: { label: 'Rejeté', class: 'badge bg-light text-danger border border-danger-subtle' },
};

const PAYMENT_STATUS_CONFIG = {
    pending: { label: 'À échoir', class: 'badge bg-light text-muted border border-secondary-subtle' },
    partiel: { label: 'Impayé Partiel', class: 'badge bg-light text-info border border-info-subtle' },
    late: { label: 'En Souffrance', class: 'badge bg-light text-danger border border-danger-subtle' },
    paid: { label: 'Réglé', class: 'badge bg-light text-success border border-success-subtle' },
};

const LABELS_MAPPING = {
    compte: 'Prélèvement sur compte Épargne',
    cash: 'Versement Espèces (Caisse)',
    digital: 'Collecte Mobile Money / Numérique',
    quinzaine: 'Quinzomadaire',
    mensuel: 'Mensuelle',
    mensuelle: 'Mensuelle',
};

export default function Show({ credit }) {
    const { flash } = usePage().props;

    // État local pour bloquer les boutons pendant les requêtes (Anti-double click)
    const [isProcessing, setIsProcessing] = useState(false);

    const payments = useMemo(() => {
        return Array.isArray(credit.payments) ? credit.payments : credit.payments?.data ?? [];
    }, [credit.payments]);

    const pagination = Array.isArray(credit.payments) ? null : credit.payments ?? null;

    const [penalties, setPenalties] = useState({});

    useEffect(() => {
        const initialPenalties = {};
        payments.forEach(payment => {
            initialPenalties[payment.id] = payment.computed_penalty ?? payment.penalite ?? 0;
        });
        setPenalties(initialPenalties);
    }, [payments]);

    const formatCurrency = value =>
        new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XAF',
            maximumFractionDigits: 0,
        }).format(value);

    const financialSummary = useMemo(() => {
        const principalAccorde = Number(credit.montant_accorde ?? credit.montant_demande ?? 0);
        const interetTotal = Number(credit.interet_total ?? 0);
        const penalitesCumulees = Number(credit.penalty_amount ?? 0);
        
        const totalAttenduGlobal = principalAccorde + interetTotal + penalitesCumulees;
        const totalRembourse = Number(credit.montant_rembourse ?? 0);
        const resteARecouvrer = Math.max(0, totalAttenduGlobal - totalRembourse);

        const encoursEnRetard = payments
            .filter(p => p.status !== 'paid' && new Date(p.due_date) < new Date())
            .reduce((sum, p) => sum + (Number(p.montant_total) + Number(p.penalite ?? 0) - Number(p.montant_paye ?? 0)), 0);

        return {
            principalAccorde,
            interetTotal,
            penalitesCumulees,
            totalAttenduGlobal,
            totalRembourse,
            resteARecouvrer,
            encoursEnRetard
        };
    }, [credit, payments]);

    const allPaymentsPaid = payments.length > 0 && payments.every(p => p.status === 'paid');
    const displayedCreditStatus = allPaymentsPaid ? 'solder' : credit.statut;

    const canPayInstallment = payment => {
        if (isProcessing) return false;
        if (!['active', 'in_arrears'].includes(displayedCreditStatus)) return false;
        if (payment.status === 'paid') return false;
        if (payment.can_pay !== undefined) return payment.can_pay;

        return payments
            .filter(p => p.echeance < payment.echeance)
            .every(p => p.status === 'paid');
    };

    const savePenalty = (payment) => {
        const amount = Math.round(Number(penalties[payment.id] ?? 0));
        if (Number.isNaN(amount) || amount < 0) {
            Swal.fire({ title: 'Erreur', text: 'Montant de pénalité invalide.', icon: 'error' });
            return;
        }

        setIsProcessing(true);
        Inertia.patch(`/admin/credits/${credit.id}/payments/${payment.id}`, { penalite: amount }, {
            preserveScroll: true,
            onStart: () => setIsProcessing(true),
            onFinish: () => setIsProcessing(false),
            onSuccess: () => {
                Swal.fire({ title: 'Mis à jour', text: 'Pénalité enregistrée.', icon: 'success', timer: 1300, showConfirmButton: false });
            },
        });
    };

    const triggerPaymentModal = (payment, remaining) => {
        const cleanRemaining = Math.max(0, Math.round(remaining));

        MySwal.fire({
            title: `Guichet d'Encaissement — Échéance #${payment.echeance}`,
            html: `
                <div style="text-align: left; font-size: 0.85rem; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;"><span>Échéance Nue :</span> <strong>${formatCurrency(payment.montant_total)}</strong></div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;"><span>Pénalités appliquées :</span> <strong>${formatCurrency(penalties[payment.id] ?? 0)}</strong></div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: #6c757d;"><span>Déjà perçu :</span> <strong>${formatCurrency(payment.montant_paye ?? 0)}</strong></div>
                    <hr style="margin: 8px 0; border: 0; border-top: 1px solid #dee2e6;"/>
                    <div style="display: flex; justify-content: space-between; color: #212529; font-weight: bold;"><span>Reste Exigible :</span> <span>${formatCurrency(cleanRemaining)}</span></div>
                </div>
                <div style="text-align: left;">
                    <label for="swal-input-amount" style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Montant à encaisser (FCFA) :</label>
                    <input 
                        id="swal-input-amount" 
                        type="number" 
                        class="form-control" 
                        style="text-align: right; font-weight: bold;"
                        value="${cleanRemaining}"
                        min="1" 
                        max="${cleanRemaining}"
                        step="1"
                    />
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Valider',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#4f5d73',
            cancelButtonColor: '#6c757d',
            focusConfirm: true,
            didOpen: () => {
                // Focus automatique et sélection complète du montant pour saisie instantanée
                const input = document.getElementById('swal-input-amount');
                if (input) {
                    input.focus();
                    input.select();
                }
            },
            preConfirm: () => {
                const inputElement = document.getElementById('swal-input-amount');
                const amount = Math.round(Number(inputElement.value));

                if (!amount || Number.isNaN(amount) || amount <= 0) {
                    Swal.showValidationMessage('Veuillez saisir un montant valide.');
                    return false;
                }
                if (amount > cleanRemaining) {
                    Swal.showValidationMessage(`Le montant saisi excède le reste dû.`);
                    return false;
                }
                return amount;
            }
        }).then(result => {
            if (result.isConfirmed && result.value) {
                Inertia.patch(`/admin/credits/${credit.id}/payments/${payment.id}`, { 
                    montant_paye: result.value 
                }, {
                    preserveScroll: true,
                    onStart: () => setIsProcessing(true),
                    onFinish: () => setIsProcessing(false),
                    onSuccess: () => {
                        Swal.fire({ title: 'Succès', text: 'Encaissement enregistré.', icon: 'success', timer: 1500, showConfirmButton: false });
                    }
                });
            }
        });
    };

    return (
        <AdminLayout>
            <div className="container-fluid px-0" style={{ color: '#2c3e50' }}>
                
                {/* Alertes système */}
                {flash.success && <div className="alert alert-success d-flex align-items-center mb-3 small shadow-sm">{flash.success}</div>}
                {flash.error && <div className="alert alert-danger d-flex align-items-center mb-3 small shadow-sm">{flash.error}</div>}

                {/* EN-TÊTE SOBRE */}
                <div className="d-flex justify-content-between align-items-center py-2 px-3 mb-3 bg-white border rounded shadow-sm">
                    <div>
                        <span className="text-uppercase text-muted fs-8 fw-semibold tracking-wider">SIG — Portefeuille Crédits</span>
                        <h1 className="fs-5 text-dark fw-bold mb-0">Dossier de Prêt #CR-{credit.id}</h1>
                    </div>
                    <div>
                        <Link href="/admin/credits" className="btn btn-sm btn-outline-secondary px-3">
                            Retour
                        </Link>
                    </div>
                </div>

                {/* CARTES INDICATEURS STYLE INSTITUTIONNEL */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <div className="card h-100 border bg-white shadow-sm p-3">
                            <span className="text-uppercase text-muted fs-8 fw-bold">Encours Global</span>
                            <h4 className="fw-bold text-dark my-1">{formatCurrency(financialSummary.totalAttenduGlobal)}</h4>
                            <div className="fs-8 text-muted border-top pt-1 mt-1">Capital + Int. + Pén.</div>
                        </div>
                    </div>
                    <div className="col-md-3">
                        <div className="card h-100 border bg-white shadow-sm p-3">
                            <span className="text-uppercase text-muted fs-8 fw-bold">Total Recouvré</span>
                            <h4 className="fw-bold text-dark my-1">{formatCurrency(financialSummary.totalRembourse)}</h4>
                            <div className="fs-8 text-muted border-top pt-1 mt-1">Amorti à : {financialSummary.totalAttenduGlobal > 0 ? Math.round((financialSummary.totalRembourse / financialSummary.totalAttenduGlobal) * 100) : 0}%</div>
                        </div>
                    </div>
                    <div className="col-md-3">
                        <div className="card h-100 border bg-white shadow-sm p-3 border-start border-warning border-3">
                            <span className="text-uppercase text-muted fs-8 fw-bold">Reste à Recouvrer</span>
                            <h4 className="fw-bold text-dark my-1">{formatCurrency(financialSummary.resteARecouvrer)}</h4>
                            <div className="fs-8 text-secondary border-top pt-1 mt-1 fw-medium">Créance active</div>
                        </div>
                    </div>
                    <div className="col-md-3">
                        <div className={`card h-100 border bg-white shadow-sm p-3 ${financialSummary.encoursEnRetard > 0 ? 'border-start border-danger border-3' : ''}`}>
                            <span className="text-uppercase text-muted fs-8 fw-bold">Portefeuille à Risque</span>
                            <h4 className={`fw-bold my-1 ${financialSummary.encoursEnRetard > 0 ? 'text-danger' : 'text-dark'}`}>{formatCurrency(financialSummary.encoursEnRetard)}</h4>
                            <div className="fs-8 text-muted border-top pt-1 mt-1">{financialSummary.encoursEnRetard > 0 ? '⚠️ Arriérés échus' : 'Aucun retard'}</div>
                        </div>
                    </div>
                </div>

                {/* INFORMATION BLOCS */}
                <div className="row g-3 mb-4">
                    <div className="col-md-4">
                        <div className="card shadow-sm border h-100">
                            <div className="card-header bg-light py-2 border-bottom">
                                <h6 className="text-uppercase text-secondary fw-bold fs-8 mb-0">I. Bénéficiaire / Membre</h6>
                            </div>
                            <div className="card-body py-2">
                                <table className="table table-sm table-borderless mb-0 fs-7">
                                    <tbody>
                                        <tr><td className="text-muted px-0">Nom & Prénom :</td><td className="fw-bold text-end">{credit.client?.nom} {credit.client?.prenom}</td></tr>
                                        <tr><td className="text-muted px-0">Classe de Risque :</td><td className="text-end"><span className={(CREDIT_STATUS_CONFIG[displayedCreditStatus] || {}).class}>{(CREDIT_STATUS_CONFIG[displayedCreditStatus] || {}).label}</span></td></tr>
                                        <tr><td className="text-muted px-0">Compte Épargne :</td><td className="text-end fw-semibold text-monospace">{credit.client?.code_compte ?? 'N/A'}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div className="col-md-4">
                        <div className="card shadow-sm border h-100">
                            <div className="card-header bg-light py-2 border-bottom">
                                <h6 className="text-uppercase text-secondary fw-bold fs-8 mb-0">II. Caractéristiques du Crédit</h6>
                            </div>
                            <div className="card-body py-2">
                                <table className="table table-sm table-borderless mb-0 fs-7">
                                    <tbody>
                                        <tr><td className="text-muted px-0">Ligne de Produit :</td><td className="fw-semibold text-end">{LABELS_MAPPING[credit.type] || credit.type}</td></tr>
                                        <tr><td className="text-muted px-0">Mode de Décaissement :</td><td className="text-end">{LABELS_MAPPING[credit.mode] || credit.mode}</td></tr>
                                        <tr><td className="text-muted px-0">Échéancier :</td><td className="text-end">{credit.nombre_echeances} éch. ({LABELS_MAPPING[credit.periodicite] || credit.periodicite})</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-4">
                        <div className="card shadow-sm border h-100">
                            <div className="card-header bg-light py-2 border-bottom">
                                <h6 className="text-uppercase text-secondary fw-bold fs-8 mb-0">III. Structure Financière</h6>
                            </div>
                            <div className="card-body py-2">
                                <table className="table table-sm table-borderless mb-0 fs-7">
                                    <tbody>
                                        <tr><td className="text-muted px-0">Capital Octroyé :</td><td className="fw-bold text-end text-dark">{formatCurrency(financialSummary.principalAccorde)}</td></tr>
                                        <tr><td className="text-muted px-0">Taux Contractuel :</td><td className="text-end fw-semibold">{credit.taux}%</td></tr>
                                        <tr><td className="text-muted px-0">Intérêts Dus :</td><td className="text-end text-dark fw-semibold">{formatCurrency(credit.interet_total ?? 0)}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {/* COMPENSATION COMPENSATOIRE */}
                {credit.emergency_withdrawal_summary?.length > 0 && (
                    <div className="card shadow-sm mb-4 border border-secondary">
                        <div className="card-header bg-light py-2 border-bottom">
                            <h6 className="text-dark mb-0 fw-bold fs-8">⚠️ Recouvrement par Compensation (Épargne de Garantie)</h6>
                        </div>
                        <div className="card-body py-2">
                            <div className="row g-2">
                                {credit.emergency_withdrawal_summary.map((item, index) => (
                                    <div key={index} className="col-md-4">
                                        <div className="p-2 rounded bg-light border fs-8 text-dark">
                                            <strong>Échéance #{item.echeance} :</strong> Retrait de <span className="fw-bold">{formatCurrency(item.amount_withdrawn)}</span>.
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* TABLEAU COMPACT ET NETTOYÉ */}
                <div className="card shadow-sm border">
                    <div className="card-header bg-light py-2 border-bottom">
                        <h5 className="card-title mb-0 fs-7 fw-bold text-dark">IV. Registre Comptable des Échéances et Amortissements</h5>
                    </div>
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-sm table-bordered align-middle mb-0 text-nowrap border-light">
                                <thead className="table-light text-uppercase fs-8 tracking-wider text-secondary border-bottom">
                                    <tr>
                                        <th className="text-center bg-white" style={{ width: '40px' }}>N°</th>
                                        <th className="bg-white">Date Exigibilité</th>
                                        <th className="text-end bg-white">Amort. Principal</th>
                                        <th className="text-end bg-white">Intérêts Dus</th>
                                        <th className="text-end bg-white">Échéance Nue</th>
                                        <th className="text-center bg-white" style={{ width: '140px' }}>Pénalités (XAF)</th>
                                        <th className="text-end bg-white">Montant Perçu</th>
                                        <th className="text-end bg-white text-dark fw-bold" style={{ backgroundColor: '#fdfefe' }}>Reste À Payer (RAP)</th>
                                        <th className="ps-3 bg-white" style={{ width: '200px' }}>Guichet / Statut</th>
                                    </tr>
                                </thead>
                                <tbody className="fs-7">
                                    {payments.map(payment => {
                                        const totalDue = Number(payment.montant_total) + Number(payment.penalite ?? 0);
                                        const paidAmount = Number(payment.montant_paye ?? 0);
                                        const remaining = Math.max(0, totalDue - paidAmount);
                                        const isOverdue = remaining > 0 && new Date(payment.due_date) < new Date();
                                        
                                        let dynamicStatus = 'pending';
                                        if (remaining === 0) {
                                            dynamicStatus = 'paid';
                                        } else if (paidAmount > 0 && remaining > 0) {
                                            dynamicStatus = 'partiel';
                                        } else if (isOverdue) {
                                            dynamicStatus = 'late';
                                        }
                                        
                                        const statusConfig = PAYMENT_STATUS_CONFIG[dynamicStatus];

                                        return (
                                            <tr key={payment.id} style={isOverdue ? { backgroundColor: '#fffdfd' } : {}}>
                                                <td className="text-center fw-bold text-muted">{payment.echeance}</td>
                                                <td>{formatDateToFR(payment.due_date)}</td>
                                                <td className="text-end text-monospace">{formatCurrency(payment.montant_principal)}</td>
                                                <td className="text-end text-monospace">{formatCurrency(payment.montant_interets)}</td>
                                                <td className="text-end text-monospace fw-semibold">{formatCurrency(payment.montant_total)}</td>
                                                <td className="text-center">
                                                    {remaining > 0 && ['active', 'in_arrears'].includes(credit.statut) ? (
                                                        <div className="input-group input-group-sm mx-auto" style={{ maxWidth: '110px' }}>
                                                            <input
                                                                type="number"
                                                                step="1"
                                                                min="0"
                                                                disabled={isProcessing}
                                                                className="form-control text-end text-monospace fs-7 px-1 py-0"
                                                                value={penalties[payment.id] ?? ''}
                                                                onChange={e => setPenalties(prev => ({ ...prev, [payment.id]: e.target.value }))}
                                                            />
                                                            <button 
                                                                className="btn btn-outline-secondary px-2 py-0 border-start-0" 
                                                                type="button" 
                                                                onClick={() => savePenalty(payment)}
                                                                disabled={isProcessing}
                                                            >
                                                                ✓
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted text-monospace fs-7">{formatCurrency(payment.computed_penalty ?? payment.penalite ?? 0)}</span>
                                                    )}
                                                </td>
                                                <td className="text-end text-secondary text-monospace">{formatCurrency(paidAmount)}</td>
                                                <td className={`text-end text-monospace fw-bold ${remaining > 0 ? 'text-dark' : 'text-success'}`} style={{ backgroundColor: '#fafbfc' }}>
                                                    {formatCurrency(remaining)}
                                                </td>
                                                <td className="ps-3">
                                                    <div className="d-flex align-items-center gap-2">
                                                        {remaining > 0 && ['active', 'in_arrears'].includes(credit.statut) ? (
                                                            <button 
                                                                className="btn btn-xs btn-outline-dark px-2 py-0 fs-8 fw-semibold" 
                                                                type="button" 
                                                                onClick={() => triggerPaymentModal(payment, remaining)}
                                                                disabled={!canPayInstallment(payment) || isProcessing}
                                                                style={{ borderRadius: '3px' }}
                                                            >
                                                                Encaisser
                                                            </button>
                                                        ) : null}
                                                        
                                                        <span className={statusConfig.class} style={{ fontSize: '11px', padding: '3px 6px' }}>{statusConfig.label}</span>
                                                    </div>

                                                    {!canPayInstallment(payment) && remaining > 0 && ['active', 'in_arrears'].includes(credit.statut) && (
                                                        <div className="fs-9 text-muted mt-1">
                                                            🔒 Attente échéance précédente
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}

                                    {payments.length === 0 && (
                                        <tr>
                                            <td colSpan="9" className="text-center py-4 text-muted bg-white">
                                                Aucune écriture disponible.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {/* Pagination */}
                {pagination && (
                    <div className="mt-2 d-flex justify-content-end gap-1 bg-white p-1 rounded border">
                        <button
                            className="btn btn-xs btn-link text-secondary text-decoration-none fs-7 px-2 py-0"
                            onClick={() => Inertia.visit(pagination.prev_page_url)}
                            disabled={!pagination.prev_page_url || isProcessing}
                        >
                            « Précédent
                        </button>
                        <button
                            className="btn btn-xs btn-link text-secondary text-decoration-none fs-7 px-2 py-0"
                            onClick={() => Inertia.visit(pagination.next_page_url)}
                            disabled={!pagination.next_page_url || isProcessing}
                        >
                            Suivant »
                        </button>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}