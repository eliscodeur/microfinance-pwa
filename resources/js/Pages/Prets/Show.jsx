import React, { useMemo } from 'react';
import { Link, usePage, useForm } from '@inertiajs/inertia-react';
import { Inertia } from '@inertiajs/inertia';
import Swal from 'sweetalert2';import withReactContent from 'sweetalert2-react-content';

import AdminLayout from '../../Layouts/AdminLayout.jsx';
import { buildScheduleFromForm, formatDateToFR, formatCurrency } from '../../Utils/creditHelpers';

const MySwal = withReactContent(Swal);
export default function Show() {
  const { props } = usePage();
  const { credit, client, diagnostic, creditProducts } = props;
const showCarnetDetails = async () => {
    if (!diagnostic?.carnet_id) {
        MySwal.fire('Erreur', 'Aucun identifiant de carnet disponible.', 'error');
        return;
    }

    MySwal.fire({
        title: 'Chargement en cours...',
        text: 'Récupération des détails du carnet',
        allowOutsideClick: false,
        didOpen: () => {
            MySwal.showLoading();
        }
    });

    try {
        const response = await axios.get(`/admin/carnets/details/${diagnostic.carnet_id}`);
        const data = response.data;
        const carnetNumero = diagnostic?.carnet_numero || '—'; // Récupération du numéro

        if (data.success) {
            MySwal.fire({
                // On utilise un fond légèrement grisé pour faire ressortir les cards blanches "shadow-sm"
                html: (
                    <div className="text-start p-3" style={{ backgroundColor: '#f8f9fc', borderRadius: '1rem' }}>
                        {data.type === 'tontine' ? (
                            // ==========================================
                            // VUE TONTINE
                            // ==========================================
                            <div className="card border-0 shadow-sm mb-2 rounded-4 overflow-hidden">
                                {/* En-tête Tontine unifié */}
                                <div className="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom border-light">
                                    <div className="d-flex align-items-center">
                                        <div 
                                            className="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded me-3" 
                                            style={{ width: '40px', height: '40px' }}
                                        >
                                            <i className="bi bi-wallet2 fs-5"></i>
                                        </div>
                                        <div className="lh-sm">
                                            <div className="fw-bold text-dark" style={{ fontSize: '0.9rem' }}>
                                                Carnet de Tontine
                                            </div>
                                            <div className="text-muted" style={{ fontSize: '0.8rem' }}>
                                                N° {carnetNumero}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <span 
                                            className="badge bg-light text-secondary border fw-medium rounded-pill px-3 py-2" 
                                            style={{ fontSize: '0.75rem' }}
                                        >
                                            {data.cycles?.length || 0} Cycle{(data.cycles?.length || 0) > 1 ? 's' : ''}
                                        </span>
                                    </div>
                                </div>

                                {/* Tableau des Cycles épuré */}
                                <div className="table-responsive">
                                    <table className="table table-borderless table-hover align-middle mb-0">
                                        <thead className="border-bottom border-light">
                                            <tr>
                                                <th className="text-muted fw-semibold text-uppercase py-3 ps-4" style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}>Période</th>
                                                <th className="text-muted fw-semibold text-uppercase py-3" style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}>Fin Prévue</th>
                                                <th className="text-muted fw-semibold text-uppercase py-3" style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}>Fin Réelle</th>
                                                <th className="text-muted fw-semibold text-uppercase py-3 text-center" style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}>Mise</th>
                                                <th className="text-muted fw-semibold text-uppercase py-3 text-center" style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}>Pointages</th>
                                                <th className="text-muted fw-semibold text-uppercase py-3 text-center" style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}>Statut</th>
                                                <th className="text-muted fw-semibold text-uppercase py-3 text-center pe-4" style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}>État</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.cycles && data.cycles.length > 0 ? data.cycles.map((cycle, idx) => (
                                                <tr key={idx}>
                                                    <td className="ps-4 fw-medium text-dark" style={{ fontSize: '0.85rem' }}>
                                                        {cycle.date_debut}
                                                    </td>
                                                    <td className="text-muted" style={{ fontSize: '0.8rem' }}>
                                                        {cycle.date_fin_prevue || '-'}
                                                    </td>
                                                    <td className="text-muted" style={{ fontSize: '0.8rem' }}>
                                                        {cycle.date_cloture_reelle || '-'}
                                                    </td>
                                                    <td className="text-center fw-semibold text-dark" style={{ fontSize: '0.85rem' }}>
                                                        {formatCurrency(cycle.mise)}
                                                    </td>
                                                    <td className="text-center text-muted" style={{ fontSize: '0.85rem' }}>
                                                        {cycle.total_pointages}
                                                    </td>
                                                    <td className="text-center">
                                                        <span 
                                                            className={`badge rounded-pill fw-medium ${cycle.statut === 'termine' ? 'bg-success bg-opacity-10 text-success' : cycle.statut === 'en_cours' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-secondary bg-opacity-10 text-secondary'}`} 
                                                            style={{ fontSize: '0.75rem' }}
                                                        >
                                                            {cycle.statut === 'en_cours' ? 'En cours' : cycle.statut === 'termine' ? 'Terminé' : cycle.statut}
                                                        </span>
                                                    </td>
                                                    <td className="text-center pe-4">
                                                        <span 
                                                            className={`badge rounded-pill fw-medium ${cycle.en_retard ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success'}`} 
                                                            style={{ fontSize: '0.75rem' }}
                                                        >
                                                            {cycle.en_retard ? 'En retard' : 'À jour'}
                                                        </span>
                                                    </td>
                                                </tr>
                                            )) : (
                                                <tr>
                                                    <td colSpan="7" className="text-center py-4 text-muted small">Aucun cycle enregistré.</td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        ) : (
                            // ==========================================
                            // VUE COMPTE ÉPARGNE
                            // ==========================================
                            <div>
                                {/* En-tête Compte */}
                                <div className="card border-0 shadow-sm mb-4 rounded-4">
                                    <div className="card-body p-3 p-md-4">
                                        <div className="d-flex align-items-center mb-3">
                                            <div 
                                                className="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded me-3" 
                                                style={{ width: '40px', height: '40px' }}
                                            >
                                                <i className="bi bi-piggy-bank fs-5"></i>
                                            </div>
                                            <div className="lh-sm">
                                                <div className="fw-bold text-dark" style={{ fontSize: '0.9rem' }}>
                                                    Compte Épargne
                                                </div>
                                                <div className="text-muted" style={{ fontSize: '0.8rem' }}>
                                                    N° {carnetNumero}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="mt-2">
                                            <div 
                                                className="text-muted text-uppercase fw-semibold mb-1" 
                                                style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}
                                            >
                                                Solde disponible
                                            </div>
                                            <div className="fw-bolder text-dark" style={{ fontSize: '1.75rem', lineHeight: '1' }}>
                                                {formatCurrency(data.solde)}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Historique des Transactions */}
                                <div className="card border-0 shadow-sm rounded-4 overflow-hidden">
                                    <div className="card-header bg-white border-bottom pt-3 pb-2">
                                        <h6 className="mb-0 text-secondary fw-bold" style={{ fontSize: '0.85rem' }}>10 Derniers mouvements</h6>
                                    </div>
                                    <div className="card-body p-0">
                                        {data.historique && data.historique.length > 0 ? (
                                            <div className="list-group list-group-flush">
                                                {data.historique.map((transaction, idx) => (
                                                    <div key={idx} className="list-group-item d-flex justify-content-between align-items-center px-3 py-3 border-light">
                                                        <div className="d-flex align-items-center gap-3 flex-grow-1">
                                                            <div 
                                                                className={`d-flex align-items-center justify-content-center rounded-circle ${transaction.type_transaction === 'Dépôt' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger'}`} 
                                                                style={{ width: '36px', height: '36px' }}
                                                            >
                                                                <i className={`bi ${transaction.type_transaction === 'Dépôt' ? 'bi-arrow-down-short' : 'bi-arrow-up-short'} fs-4`}></i>
                                                            </div>
                                                            <div className="lh-sm">
                                                                <div className="fw-medium text-dark" style={{ fontSize: '0.85rem' }}>
                                                                    {transaction.type_transaction}
                                                                </div>
                                                                <small className="text-muted" style={{ fontSize: '0.70rem' }}>
                                                                    {transaction.date}
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div className="text-end">
                                                            <div className={`fw-semibold ${transaction.type_transaction === 'Dépôt' ? 'text-success' : 'text-danger'}`} style={{ fontSize: '0.9rem' }}>
                                                                {transaction.type_transaction === 'Dépôt' ? '+' : '-'} {formatCurrency(transaction.montant)}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="p-4 text-center text-muted">
                                                <i className="bi bi-journal-text fs-3 opacity-50 d-block mb-2"></i>
                                                <span style={{ fontSize: '0.85rem' }}>Aucune transaction récente</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                ),
                width: '850px',
                showConfirmButton: true,
                confirmButtonText: 'Fermer',
                buttonsStyling: false, // Désactive le style par défaut de Swal pour utiliser vos classes Bootstrap
                customClass: {
                    popup: 'rounded-4 shadow-lg p-0', // p-0 enlève le padding interne pour que le fond gris remplisse tout
                    confirmButton: 'btn btn-dark px-4 py-2 fw-medium rounded-pill mb-3 mt-2'
                }
            });
        }
    } catch (error) {
        console.error("Erreur de récupération :", error);
        const errorMsg = error.response?.data?.error || 'Impossible de récupérer les détails. Vérifiez la connexion.';
        MySwal.fire({
            title: 'Erreur',
            text: errorMsg,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    }
};
  const getStatusBadge = statut => {
    switch (statut) {
      case 'pending':
      case 'soumis':
      case 'en_etude':
        return <span className="badge bg-secondary-subtle text-secondary border px-3 py-2 fs-6">En étude</span>;
      case 'approved':
      case 'approuve':
        return <span className="badge bg-success-subtle text-success border px-3 py-2 fs-6">Approuvé</span>;
      case 'active':
        return <span className="badge bg-primary-subtle text-primary border px-3 py-2 fs-6">Décaissement effectué</span>;
      case 'in_arrears':
        return <span className="badge bg-danger-subtle text-danger border px-3 py-2 fs-6">En retard</span>;
      case 'solder':
      case 'solde':
        return <span className="badge bg-info-subtle text-info border px-3 py-2 fs-6">Soldé</span>;
      case 'rejected':
      case 'rejete':
        return <span className="badge bg-light text-muted border px-3 py-2 fs-6">Rejeté</span>;
      default:
        return <span className="badge bg-light text-dark border px-3 py-2 fs-6">{statut ?? 'Inconnu'}</span>;
    }
  };

  const approvedAmount = credit?.montant_accorde ?? credit?.montant_demande ?? credit?.montant ?? 0;
  const requestedAmount = credit?.montant_demande ?? credit?.montant ?? 0;
  const proposedRate = credit?.taux ?? 0;
  const frais_dossier = credit?.frais_dossier;
  const differe = credit?.differe;
  const taux_manuel= credit?.taux_manuel ?? '';
  const initialNumberOfInstallments = credit?.nombre_echeances ?? 1;
  const initialMode = credit?.mode ?? 'degressif';
  const initialPeriodicity = credit?.periodicite ?? 'mensuelle';
  const initialStartDate = credit?.date_debut ? credit.date_debut.split('T')[0] : new Date().toISOString().slice(0, 10);
  const initialProductId = credit?.credit_product_id ?? (creditProducts.length > 0 ? creditProducts[0].id : null);
  const initialObjectId = credit?.credit_object_id ?? (creditProducts.length > 0 && creditProducts[0].creditObjects.length > 0 ? creditProducts[0].creditObjects[0].id : null);
 
  const approveForm = useForm({
    action: 'approuve',
    montant_accorde: approvedAmount,
    taux: proposedRate,
    taux_manuel: taux_manuel,
    differe: differe,
    date_debut: initialStartDate,
    frais_dossier: frais_dossier,
    nombre_echeances: initialNumberOfInstallments,
    mode: initialMode,
    periodicite: initialPeriodicity,
    // credit_product_id: initialProductId,
    // credit_object_id: initialObjectId,
  });

  const rejectForm = useForm({ action: 'rejete', motif: '' });

  const approvalDifference = {
    montant: Number(approveForm.data.montant_accorde) !== Number(requestedAmount),
    taux: Number(approveForm.data.taux) !== Number(proposedRate),
    nombre_echeances: Number(approveForm.data.nombre_echeances) !== Number(initialNumberOfInstallments),
    periodicite: approveForm.data.periodicite !== initialPeriodicity,
    mode: approveForm.data.mode !== initialMode,
    date_debut: approveForm.data.date_debut !== initialStartDate,
  };
  const hasApprovalDifferences = Object.values(approvalDifference).some(Boolean);

  const requestSchedule = useMemo(() => buildScheduleFromForm({
    montant_demande: requestedAmount,
    taux: proposedRate,
    taux_manuel: credit?.taux_manuel ?? '',
    nombre_echeances: initialNumberOfInstallments,
    mode: initialMode,
    periodicite: initialPeriodicity,
    date_debut: credit?.date_debut ?? initialStartDate,
  }), [requestedAmount, proposedRate, initialNumberOfInstallments, initialMode, initialPeriodicity, credit, initialStartDate]);

  const approvedSchedule = useMemo(() => {
    const montantOptionnel = parseFloat(approveForm.data.montant_accorde) || 0;
    const tauxOptionnel = parseFloat(approveForm.data.taux) || 0;
    const echeancesOptionnel = parseInt(approveForm.data.nombre_echeances, 10) || 1;

    return buildScheduleFromForm({
      montant_demande: montantOptionnel,
      taux: tauxOptionnel,
      taux_manuel: credit?.taux_manuel ?? '',
      nombre_echeances: echeancesOptionnel,
      mode: approveForm.data.mode,
      periodicite: approveForm.data.periodicite,
      date_debut: approveForm.data.date_debut || new Date().toISOString().slice(0, 10),
    });
  }, [approveForm.data.montant_accorde, approveForm.data.taux, approveForm.data.nombre_echeances, approveForm.data.mode, approveForm.data.periodicite, approveForm.data.date_debut, credit]);

  const requestScheduleSummary = useMemo(() => ({
    totalInterest: requestSchedule.reduce((sum, row) => sum + row.interest, 0),
    totalDue: requestSchedule.reduce((sum, row) => sum + row.total, 0),
  }), [requestSchedule]);

  const approvedScheduleSummary = useMemo(() => ({
    totalInterest: approvedSchedule.reduce((sum, row) => sum + row.interest, 0),
    totalDue: approvedSchedule.reduce((sum, row) => sum + row.total, 0),
  }), [approvedSchedule]);

  function confirmAction(title, text, callback) {
    Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#4f46e5',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Continuer',
      cancelButtonText: 'Annuler',
    }).then(result => { if (result.isConfirmed) callback(); });
  }

  function submitApprove(e) {
    e.preventDefault();
    confirmAction('Approuver le dossier ?', 'Le dossier sera validé avec les conditions financières saisies.', () => {
      approveForm.post(`/admin/prets/${credit.id}/valider`);
    });
  }

  function submitReject(e) {
    e.preventDefault();
    if (!rejectForm.data.motif.trim()) {
      Swal.fire('Erreur', 'Veuillez saisir un motif de refus.', 'error');
      return;
    }
    confirmAction('Refuser le dossier ?', 'Le dossier sera marqué comme rejeté.', () => {
      rejectForm.post(`/admin/prets/${credit.id}/valider`);
    });
  }

  function markEnEtude() {
    confirmAction('Mettre en étude ?', 'Le statut du dossier passera en cours d’analyse.', () => {
      Inertia.post(`/admin/prets/${credit.id}/valider`, { action: 'en_etude' });
    });
  }

  const isPending = ['pending', 'soumis', 'en_etude'].includes(credit?.statut);
  const isApproved = credit?.statut === 'approved';

  function doDecaissement() {
    confirmAction('Confirmer le décaissement ?', 'Le crédit passera au statut actif.', () => {
      Inertia.post(`/admin/prets/${credit.id}/decaisser`);
    });
  }

  return (
    <AdminLayout>
      <div className="container-fluid px-4 py-3 bg-light-subtle">
        {/* Header Épuré */}
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-2">
          <div>
            <div className="d-flex align-items-center gap-3">
              <h1 className="h4 mb-0 text-dark fw-bold">Dossier de Crédit #{credit.id}</h1>
              {getStatusBadge(credit?.statut)}
            </div>
            <p className="text-muted small mb-0 mt-1">Analyse et arbitrage des plans de remboursement.</p>
          </div>
          <div>
            <Link href="/admin/prets" className="btn btn-sm btn-outline-secondary px-3">
              <i className="bi bi-arrow-left me-2"></i>Retour
            </Link>
          </div>
        </div>

        {/* SECTION HAUTE : Diagnostics & Formulaire */}
        <div className="row g-3 mb-4">
          {/* Diagnostic Gauche */}
        <div className="col-lg-4">
  
  {/* 1. INFORMATIONS DU CLIENT */}
  <div className="card shadow-sm border-light mb-3">
    <div className="card-body py-3">
      <div className="d-flex align-items-center mb-3">
        <div className="bg-secondary-subtle text-secondary rounded-circle px-3 py-2 fw-bold me-3">
          {client ? client.nom.charAt(0) : '—'}
        </div>
        <div>
          <h6 className="mb-0 fw-bold text-dark">{client ? `${client.nom} ${client.prenom}` : '—'}</h6>
          <small className="text-muted">Client Épargnant</small>
        </div>
      </div>
      {/* <div className="d-flex justify-content-between py-2 border-bottom border-light small">
        <span className="text-muted">Total épargné</span>
        <span className="fw-semibold text-dark">{formatCurrency(diagnostic?.totalEpargne ?? 0)}</span>
      </div> */}
      <div className="d-flex justify-content-between py-2 border-bottom border-light small">
        <span className="text-muted">Carnets actifs</span>
        <span className="fw-semibold text-dark">{diagnostic?.nombreCarnets ?? 0}</span>
      </div>
      <div className="d-flex justify-content-between py-2 small">
        <span className="text-muted">Régularité</span>
        <span className="fw-semibold text-dark">{diagnostic?.regularitePourcent ?? 'N/A'}%</span>
      </div>
    </div>
  </div>

  <div className="card shadow-sm border-light mb-3">
    <div className="card-body py-3 d-flex justify-content-between align-items-center">
      <div>
        <span className="text-muted text-uppercase fw-bold tracking-wider d-block mb-1" style={{ fontSize: '10px' }}>
          Carnet Associé
        </span>
        <div className="fw-bold text-dark text-capitalize fs-6">
          {diagnostic?.type_carnet || 'Non défini'}
        </div>
        <small className="text-muted">N° {diagnostic?.carnet_numero || '—'}</small>
      </div>
      
      {/* Appel de la fonction asynchrone ici */}
      <button 
        className="btn btn-outline-primary btn-sm px-3 fw-medium" 
        onClick={showCarnetDetails}
        disabled={!diagnostic?.carnet_id} 
      >
        <i className="bi bi-eye me-1"></i> Voir détails
      </button>
    </div>
  </div>

  {/* 3. DEMANDE INITIALE */}
  <div className="card shadow-sm border-light">
    <div className="card-header bg-transparent border-0 pt-3 pb-0">
      <span className="text-muted text-uppercase fw-bold tracking-wider" style={{ fontSize: '11px' }}>Demande Initiale</span>
    </div>
    <div className="card-body">
      <div className="mb-3">
        <h4 className="fw-bold text-dark mb-1">{formatCurrency(requestedAmount)}</h4>
      </div>
      <div className="row g-2 text-muted small border-top pt-2">
        <div className="col-4">Taux: <span className="fw-semibold text-dark">{taux_manuel !== '' ? taux_manuel : proposedRate}%</span></div>
        <div className="col-4 text-center">Échéances: <span className="fw-semibold text-dark">{initialNumberOfInstallments}</span></div>
        <div className="col-4 text-end text-capitalize">Mode: <span className="fw-semibold text-dark">{initialMode}</span></div>
      </div>
    </div>
  </div>

</div>

          {/* Formulaire d'Ajustement */}
          <div className="col-lg-8">
            <div className="card shadow-sm border-light h-100">
              <div className="card-body d-flex flex-column justify-content-between">
                <div>
                  <div className="d-flex justify-content-between align-items-center mb-3">
                    <h6 className="fw-bold text-dark mb-0">Paramètres d'Octroi Révisés</h6>
                    {isPending && (
                      <button className="btn btn-sm btn-link text-muted p-0 text-decoration-none" onClick={markEnEtude}>
                        <i className="bi bi-hourglass-split me-1"></i> Mettre en instruction
                      </button>
                    )}
                  </div>

                  {isPending ? (
                    <form onSubmit={submitApprove} id="approve-form">
                      <div className="row g-3">
                        {/* --- BLOC 1 : IDENTIFICATION PRODUIT --- */}
                        <div className="col-md-6">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Produit de crédit</label>
                          <select className="form-select form-select-sm" value={credit.credit_product_id} disabled>
                            <option value="">Sélectionnez un produit</option>
                            {creditProducts.map((prod) => (
                              <option key={prod.id} value={prod.id}>
                                {prod.nom} {/* Remplacez 'nom' par la colonne réelle (ex: libelle) */}
                              </option>
                            ))}
                          </select>
                        </div>

                        {/* 2. Sélection de l'Objet du crédit */}
                        <div className="col-md-6">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Objet du crédit</label>
                          <select className="form-select form-select-sm" value={credit.credit_object_id} disabled>
                            <option value="">Sélectionnez un objet</option>
                            {/* On trouve le produit sélectionné pour afficher ses objets associés */}
                            {creditProducts
                              .find((p) => p.id === credit.credit_product_id)
                              ?.credit_objects.map((obj) => (
                                <option key={obj.id} value={obj.id}>
                                  {obj.nom} {/* Remplacez 'nom' par la colonne réelle (ex: libelle) */}
                                </option>
                              ))}
                          </select>
                        </div>

                        {/* --- BLOC 2 : PARAMÈTRES FINANCIERS --- */}
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Montant accordé (FCFA)</label>
                          <input className="form-control form-control-sm fw-semibold" type="number" value={approveForm.data.montant_accorde} onChange={e => approveForm.setData('montant_accorde', e.target.value)} />
                        </div>
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Taux d'intérêt (%)</label>
                          <input className="form-control form-control-sm fw-semibold" type="number" step="0.01" value={approveForm.data.taux} onChange={e => approveForm.setData('taux', e.target.value)} readOnly/>
                        </div>
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Taux manuel (%)</label>
                          <input className="form-control form-control-sm fw-semibold" type="number" step="0.01" value={approveForm.data.taux_manuel} onChange={e => approveForm.setData('taux_manuel', e.target.value)} />
                        </div>
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Nb Échéances</label>
                          <input className="form-control form-control-sm" type="number" value={approveForm.data.nombre_echeances} onChange={e => approveForm.setData('nombre_echeances', e.target.value)} />
                        </div>

                        {/* --- BLOC 3 : LOGISTIQUE & CONDITIONS --- */}
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Périodicité</label>
                          <select className="form-select form-select-sm" value={approveForm.data.periodicite} onChange={e => approveForm.setData('periodicite', e.target.value)}>
                            <option value="quinzaine">Quinzaine</option>
                            <option value="mensuelle">Mensuelle</option>
                            {/* <option value="hebdomadaire">Hebdomadaire</option> */}
                          </select>
                        </div>
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Mode de calcul</label>
                          <select className="form-select form-select-sm" value={approveForm.data.mode} onChange={e => approveForm.setData('mode', e.target.value)}>
                            <option value="fixe">Fixe (Flat)</option>
                            <option value="degressif">Dégressif</option>
                          </select>
                        </div>
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Différé (Échéances)</label>
                          <input className="form-control form-control-sm" type="number" value={approveForm.data.differe ?? 0} onChange={e => approveForm.setData('differe', e.target.value)} />
                        </div>
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Date de début</label>
                          <input className="form-control form-control-sm" type="date" value={approveForm.data.date_debut} onChange={e => approveForm.setData('date_debut', e.target.value)} />
                        </div>

                        {/* --- BLOC 4 : FRAIS & GARANTIES --- */}
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Frais de dossier (FCFA)</label>
                          <input className="form-control form-control-sm" type="number" value={approveForm.data.frais_dossier} onChange={e => approveForm.setData('frais_dossier', e.target.value)} readOnly />
                        </div>
                        <div className="col-md-4">
                          <label className="form-label text-muted mb-1" style={{ fontSize: '12px' }}>Garantie / Nantissement</label>
                          <input className="form-control form-control-sm" type="text" value={approveForm.data.garanties} onChange={e => approveForm.setData('garanties', e.target.value)} placeholder="Ex: Épargne bloquée..." />
                        </div>
                      </div>
                    </form>
                  ) : (
                    <div className="text-muted py-4 text-center small bg-light rounded border">
                      Dossier traité. Les modifications ne sont plus autorisées.
                    </div>
                  )}
                </div>

                <div className="mt-3 pt-3 border-top border-light d-flex justify-content-between align-items-center">
                  {isPending && (
                    <button className="btn btn-sm btn-dark px-4" type="submit" form="approve-form" disabled={approveForm.processing}>
                      <i className="bi bi-check-circle me-2"></i>Valider &amp; Approuver
                    </button>
                  )}
                  {isApproved && (
                    <button className="btn btn-sm btn-primary w-100 fw-bold" onClick={doDecaissement}>Confirmer le décaissement</button>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* SECTION BASSE : LES ÉCHÉANCIERS CÔTE À CÔTE SUR TOUTE LA LIGNE */}
        <div className="row g-3">
          <div className="col-12">
            <div className="row g-3">
              {/* Échéancier Initial */}
              <div className="col-md-6">
                <div className="card shadow-sm border-light">
                  <div className="card-header bg-light border-bottom py-3">
                    <div className="d-flex justify-content-between align-items-center">
                      <span className="fw-bold text-secondary small text-uppercase">Échéancier Initial (Demandé)</span>
                    </div>
                    <div className="d-flex gap-3 mt-1 text-muted border-top pt-2" style={{ fontSize: '12px' }}>
                      <span>Intérêts: <strong>{formatCurrency(requestScheduleSummary.totalInterest)}</strong></span>
                      <span>Total dû: <strong>{formatCurrency(requestScheduleSummary.totalDue)}</strong></span>
                    </div>
                  </div>
                  <div className="card-body p-0">
                    <div className="table-responsive" style={{ maxHeight: '350px', overflowY: 'auto' }}>
                      <table className="table table-sm table-hover align-middle mb-0 text-center" style={{ fontSize: '12px' }}>
                        <thead className="table-light sticky-top">
                          <tr>
                            <th className="text-start ps-3">N°</th>
                            <th className="text-start">Date</th>
                            <th className="text-end">Intérêts</th>
                            <th className="text-end pe-3">Échéance</th>
                          </tr>
                        </thead>
                        <tbody>
                          {requestSchedule.map((row, i) => (
                            <tr key={i}>
                              <td className="text-start ps-3 fw-semibold text-muted">{i + 1}</td>
                              <td className="text-start text-muted">{formatDateToFR(row.date)}</td>
                              <td className="text-end text-muted font-monospace">+{formatCurrency(row.interest)}</td>
                              <td className="text-end pe-3 font-monospace fw-semibold text-dark">{formatCurrency(row.total)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              {/* Échéancier Révisé */}
              <div className="col-md-6">
                <div className="card shadow-sm border-light">
                  <div className="card-header bg-light border-bottom py-3">
                    <div className="d-flex justify-content-between align-items-center">
                      <span className="fw-bold text-dark small text-uppercase">Échéancier Révisé (Simulé)</span>
                    </div>
                    <div className="d-flex gap-3 mt-1 text-muted border-top pt-2" style={{ fontSize: '12px' }}>
                      <span>Intérêts: <strong>{formatCurrency(approvedScheduleSummary.totalInterest)}</strong></span>
                      <span>Total dû: <strong className="text-primary">{formatCurrency(approvedScheduleSummary.totalDue)}</strong></span>
                    </div>
                  </div>
                  <div className="card-body p-0">
                    <div className="table-responsive" style={{ maxHeight: '350px', overflowY: 'auto' }}>
                      <table className="table table-sm table-hover align-middle mb-0 text-center" style={{ fontSize: '12px' }}>
                        <thead className="table-light sticky-top">
                          <tr>
                            <th className="text-start ps-3">N°</th>
                            <th className="text-start">Date</th>
                            <th className="text-end">Intérêts</th>
                            <th className="text-end pe-3">Échéance</th>
                          </tr>
                        </thead>
                        <tbody>
                          {approvedSchedule.map((row, i) => (
                            <tr key={i}>
                              <td className="text-start ps-3 fw-semibold text-muted">{i + 1}</td>
                              <td className="text-start text-dark">{formatDateToFR(row.date)}</td>
                              <td className="text-end text-muted font-monospace">+{formatCurrency(row.interest)}</td>
                              <td className="text-end pe-3 font-monospace fw-bold text-primary">{formatCurrency(row.total)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Zone de Refus en bas */}
        {isPending && (
          <div className="card shadow-sm border-light bg-light-subtle p-3 rounded-3 mt-3">
            <h6 className="fw-bold text-muted mb-2" style={{ fontSize: '12px' }}>Rejeter la demande</h6>
            <form onSubmit={submitReject} className="row g-2">
              <div className="col-sm-10">
                <input className="form-control form-control-sm" type="text" placeholder="Indiquez explicitement le motif du refus..." value={rejectForm.data.motif} onChange={e => rejectForm.setData('motif', e.target.value)} />
              </div>
              <div className="col-sm-2">
                <button className="btn btn-sm btn-outline-danger w-100" type="submit" disabled={rejectForm.processing}>Refuser</button>
              </div>
            </form>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}