import React, { useMemo } from 'react';
import { Link, usePage, useForm } from '@inertiajs/inertia-react';
import { Inertia } from '@inertiajs/inertia';
import Swal from 'sweetalert2';
import AdminLayout from '../../Layouts/AdminLayout.jsx';
import { buildScheduleFromForm, formatDateToFR, formatCurrency } from '../../Utils/creditHelpers';

export default function Show() {
  const { props } = usePage();
  const { credit, client, diagnostic } = props;

  const statusLabel = statut => {
    switch (statut) {
      case 'pending':
      case 'soumis':
      case 'en_etude':
        return 'En étude';
      case 'approved':
      case 'approuve':
        return 'Approuvé';
      case 'active':
        return 'Décaissement effectué';
      case 'in_arrears':
        return 'En retard';
      case 'solder':
      case 'solde':
        return 'Soldé';
      case 'rejected':
      case 'rejete':
        return 'Rejeté';
      default:
        return statut ?? 'Inconnu';
    }
  };

  const approvedAmount = credit?.montant_accorde ?? credit?.montant_demande ?? credit?.montant ?? 0;
  const requestedAmount = credit?.montant_demande ?? credit?.montant ?? 0;
  const proposedRate = credit?.taux_propose ?? credit?.taux ?? 0;

  const approveForm = useForm({
    action: 'approuve',
    montant_accorde: approvedAmount,
    taux: proposedRate,
    date_debut: credit?.date_debut ? credit.date_debut.split('T')[0] : new Date().toISOString().slice(0, 10),
  });

  const requestSchedule = useMemo(() => buildScheduleFromForm({
    montant_demande: requestedAmount,
    taux: proposedRate,
    taux_manuelle: credit?.taux_manuelle ?? '',
    nombre_echeances: credit?.nombre_echeances ?? 1,
    mode: credit?.mode ?? 'degressif',
    periodicite: credit?.periodicite ?? 'mensuelle',
    date_debut: credit?.date_debut ?? new Date().toISOString().slice(0, 10),
  }), [requestedAmount, proposedRate, credit]);

  const approvedSchedule = useMemo(() => buildScheduleFromForm({
    montant_demande: approveForm.montant_accorde,
    taux: approveForm.taux,
    taux_manuelle: credit?.taux_manuelle ?? '',
    nombre_echeances: credit?.nombre_echeances ?? 1,
    mode: credit?.mode ?? 'degressif',
    periodicite: credit?.periodicite ?? 'mensuelle',
    date_debut: (approveForm.date_debut || credit?.date_debut) ?? new Date().toISOString().slice(0, 10),
  }), [approveForm, credit]);

  const requestScheduleSummary = useMemo(() => ({
    totalInterest: requestSchedule.reduce((sum, row) => sum + row.interest, 0),
    totalDue: requestSchedule.reduce((sum, row) => sum + row.total, 0),
  }), [requestSchedule]);

  const approvedScheduleSummary = useMemo(() => ({
    totalInterest: approvedSchedule.reduce((sum, row) => sum + row.interest, 0),
    totalDue: approvedSchedule.reduce((sum, row) => sum + row.total, 0),
  }), [approvedSchedule]);

  const rejectForm = useForm({ action: 'rejete', motif: '' });

  function confirmAction(title, text, callback) {
    Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Oui, continuer',
      cancelButtonText: 'Annuler',
    }).then(result => {
      if (result.isConfirmed) {
        callback();
      }
    });
  }

  function submitApprove(e) {
    e.preventDefault();
    confirmAction('Approuver le dossier ?', 'Le dossier sera approuvé et le statut sera mis à jour.', () => {
      approveForm.post(`/admin/prets/${credit.id}/valider`);
    });
  }

  function submitReject(e) {
    e.preventDefault();
    confirmAction('Refuser le dossier ?', 'Le dossier sera refusé et le motif de refus enregistré.', () => {
      rejectForm.post(`/admin/prets/${credit.id}/valider`);
    });
  }

  function markEnEtude() {
    confirmAction('Mettre en étude ?', 'Le dossier restera en attente d’analyse interne.', () => {
      Inertia.post(`/admin/prets/${credit.id}/valider`, { action: 'en_etude' });
    });
  }

  const isPending = ['pending', 'soumis', 'en_etude'].includes(credit?.statut);
  const isApproved = credit?.statut === 'approved';

  function doDecaissement() {
    confirmAction('Confirmer le décaissement ?', 'Confirmer le décaissement et génération de l’échéancier.', () => {
      Inertia.post(`/admin/prets/${credit.id}/decaisser`);
    });
  }

  return (
    <AdminLayout>
      <div>
        <div className="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 className="h3">Fiche d'instruction — Dossier #{credit.id}</h1>
            <p className="text-muted mb-0">Analyse 360° du profil client et workflow comité de crédit.</p>
          </div>
          <div>
            <Link href="/admin/prets" className="btn btn-outline-secondary me-2">Retour</Link>
          </div>
        </div>

        <div className="row mt-4">
          <div className="col-md-4">
            <div className="card mb-3">
              <div className="card-header">Diagnostic Client</div>
              <div className="card-body">
                <p><strong>Nom :</strong> {client ? `${client.nom} ${client.prenom}` : '—'}</p>
                <p><strong>Nombre de carnets :</strong> {diagnostic?.nombreCarnets ?? 0}</p>
                <p><strong>Total épargné :</strong> {diagnostic?.totalEpargne ?? 0}</p>
                <p><strong>Cycles complétés :</strong> {diagnostic?.cyclesCompletes ?? 0}</p>
                <p><strong>Régularité :</strong> {diagnostic?.regularitePourcent ?? 'N/A'}%</p>
              </div>
            </div>

            <div className="card">
              <div className="card-header">Demande initiale</div>
              <div className="card-body">
                <p><strong>Montant demandé :</strong> {formatCurrency(requestedAmount)}</p>
                <p><strong>Taux nominal annuel proposé :</strong> {proposedRate}%</p>
                <p><strong>Périodicité :</strong> {credit?.periodicite ?? '—'}</p>
                <p><strong>Nombre d’échéances :</strong> {credit?.nombre_echeances ?? '—'}</p>
                <p><strong>Date de début demandée :</strong> {formatDateToFR(credit?.date_debut)}</p>
                <p><strong>Durée :</strong> {credit?.duree ?? '—'}</p>
              </div>
            </div>
          </div>

          <div className="col-md-8">
            <div className="card mb-3">
              <div className="card-header">Actions du comité</div>
              <div className="card-body">
                {isPending ? (
                  <>
                    <div className="mb-3">
                      <button className="btn btn-secondary me-2" onClick={markEnEtude}>Mettre en étude</button>

                      <form onSubmit={submitApprove} className="d-inline">
                        <div className="mb-2">
                          <label className="form-label">Montant accordé</label>
                          <input className="form-control" type="number" value={approveForm.montant_accorde} onChange={e => approveForm.setData('montant_accorde', e.target.value)} />
                        </div>
                        <div className="mb-2">
                          <label className="form-label">Taux final (%)</label>
                          <input className="form-control" type="number" value={approveForm.taux} onChange={e => approveForm.setData('taux', e.target.value)} />
                        </div>
                        <div className="mb-2">
                          <label className="form-label">Date de début de l'échéancier</label>
                          <input className="form-control" type="date" value={approveForm.date_debut} onChange={e => approveForm.setData('date_debut', e.target.value)} />
                        </div>
                        {Number(approveForm.montant_accorde) !== Number(requestedAmount) || Number(approveForm.taux) !== Number(proposedRate) ? (
                          <div className="alert alert-warning mt-3">
                            <strong>Attention :</strong> les conditions d’approbation diffèrent de la demande initiale.
                            <ul className="mb-0">
                              {Number(approveForm.montant_accorde) !== Number(requestedAmount) && (
                                <li>Montant demandé : {formatCurrency(requestedAmount)} — Montant décisionnel : {formatCurrency(approveForm.montant_accorde)}</li>
                              )}
                              {Number(approveForm.taux) !== Number(proposedRate) && (
                                <li>Taux proposé : {proposedRate}% — Taux décisionnel : {approveForm.taux}%</li>
                              )}
                            </ul>
                            <div>Le plan de remboursement sera basé sur les conditions validées par le comité.</div>
                          </div>
                        ) : null}
                        <button className="btn btn-success me-2" type="submit">Approuver</button>
                      </form>

                      <div className="mt-3">
                        <form onSubmit={submitReject}>
                          <div className="mb-2">
                            <label className="form-label">Motif de refus</label>
                            <input className="form-control" type="text" value={rejectForm.motif} onChange={e => rejectForm.setData('motif', e.target.value)} />
                          </div>
                          <button className="btn btn-danger" type="submit">Refuser</button>
                        </form>
                      </div>
                    </div>
                  </>
                ) : (
                  <div className="alert alert-secondary">
                    <strong>Dossier traité :</strong> {statusLabel(credit?.statut)}
                  </div>
                )}
              </div>
            </div>

            {isApproved && (
              <div className="card mb-3">
                <div className="card-header">Décaissement</div>
                <div className="card-body">
                  <button className="btn btn-primary" onClick={doDecaissement}>Confirmer le décaissement (Mise en place)</button>
                </div>
              </div>
            )}

            <div className="card mb-3">
              <div className="card-header">Échéancier de la demande initiale</div>
              <div className="card-body">
                <p className="mb-2"><strong>Montant demandé :</strong> {formatCurrency(requestedAmount)} • <strong>Taux :</strong> {proposedRate}%</p>
                <p className="mb-2"><strong>Date de début :</strong> {formatDateToFR(credit?.date_debut)}</p>
                <div className="row gy-2 mb-3">
                  <div className="col-md-6">
                    <div className="border rounded-3 p-3 bg-light">
                      <div className="text-muted">Intérêt total</div>
                      <div className="fs-5 fw-bold">{formatCurrency(requestScheduleSummary.totalInterest)}</div>
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="border rounded-3 p-3 bg-light">
                      <div className="text-muted">Total à rembourser</div>
                      <div className="fs-5 fw-bold">{formatCurrency(requestScheduleSummary.totalDue)}</div>
                    </div>
                  </div>
                </div>
                <div className="table-responsive">
                  <table className="table table-sm mb-0">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Principal</th>
                        <th>Intérêts</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      {requestSchedule.map(item => (
                        <tr key={item.numero}>
                          <td>{item.numero}</td>
                          <td>{formatDateToFR(item.date)}</td>
                          <td>{formatCurrency(item.principal)}</td>
                          <td>{formatCurrency(item.interest)}</td>
                          <td>{formatCurrency(item.total)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div className="card mb-3">
              <div className="card-header">Échéancier validé par le comité</div>
              <div className="card-body">
                <p className="mb-2"><strong>Montant validé :</strong> {formatCurrency(approveForm.montant_accorde)} • <strong>Taux :</strong> {approveForm.taux}%</p>
                <p className="mb-2"><strong>Date de début validée :</strong> {formatDateToFR(approveForm.date_debut)}</p>
                <div className="row gy-2 mb-3">
                  <div className="col-md-6">
                    <div className="border rounded-3 p-3 bg-light">
                      <div className="text-muted">Intérêt total</div>
                      <div className="fs-5 fw-bold">{formatCurrency(approvedScheduleSummary.totalInterest)}</div>
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="border rounded-3 p-3 bg-light">
                      <div className="text-muted">Total à rembourser</div>
                      <div className="fs-5 fw-bold">{formatCurrency(approvedScheduleSummary.totalDue)}</div>
                    </div>
                  </div>
                </div>
                <div className="table-responsive">
                  <table className="table table-sm mb-0">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Principal</th>
                        <th>Intérêts</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      {approvedSchedule.map(item => (
                        <tr key={item.numero}>
                          <td>{item.numero}</td>
                          <td>{formatDateToFR(item.date)}</td>
                          <td>{formatCurrency(item.principal)}</td>
                          <td>{formatCurrency(item.interest)}</td>
                          <td>{formatCurrency(item.total)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div className="card">
              <div className="card-header">Historique et notes</div>
              <div className="card-body">
                <p><strong>Statut actuel :</strong> {statusLabel(credit?.statut)}</p>
                <p><strong>Montant demandé :</strong> {formatCurrency(requestedAmount)}</p>
                <p><strong>Montant validé :</strong> {formatCurrency(approvedAmount)}</p>
                <p><strong>Taux appliqué :</strong> {(Number(approveForm.taux) ? approveForm.taux : proposedRate)}%</p>
                <p><strong>Date de début validée :</strong> {formatDateToFR(approveForm.date_debut)}</p>
                <p><strong>Date de demande :</strong> {formatDateToFR(credit?.date_demande)}</p>
                <p><strong>Date d'approbation :</strong> {formatDateToFR(credit?.approved_at)}</p>
                <p><strong>Date de dernier changement :</strong> {formatDateToFR(credit?.updated_at)}</p>
                {credit?.metadata?.rejection_reason && (
                  <div className="alert alert-warning mt-3" role="alert">
                    <strong>Motif de refus :</strong> {credit.metadata.rejection_reason}
                  </div>
                )}
                {credit?.metadata?.preview && (
                  <div className="alert alert-info mt-3" role="alert">
                    Ce dossier a été saisi en mode prévisualisation.
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
