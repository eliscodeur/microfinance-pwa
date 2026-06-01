import React, { useState } from 'react';
import { Link } from '@inertiajs/inertia-react';
import AdminLayout from '../../Layouts/AdminLayout.jsx';

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
      return statut;
  }
};

const formatCurrency = amount => {
  if (amount === null || amount === undefined || amount === '') {
    return '—';
  }

  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    maximumFractionDigits: 0,
  }).format(Number(amount));
};

const formatDateToFR = value => {
  if (!value) {
    return '—';
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return parsed.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

export default function Index(props) {
  const { creditsNonApprouves = [], creditsApprouves = [], historique = [] } = props;
  const [tab, setTab] = useState('pending');

  return (
    <AdminLayout>
      <div>
        <div className="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 className="h3">Gestion des dossiers de crédit</h1>
            <p className="text-muted mb-0">Onglets de suivi, dossier en attente, approuvé et historique.</p>
          </div>
        </div>

        <ul className="nav nav-tabs mt-3">
          <li className="nav-item">
            <button className={`nav-link ${tab === 'pending' ? 'active' : ''}`} onClick={() => setTab('pending')}>
              En attente d'approbation
            </button>
          </li>
          <li className="nav-item">
            <button className={`nav-link ${tab === 'approved' ? 'active' : ''}`} onClick={() => setTab('approved')}>
              Approuvés / En attente de fonds
            </button>
          </li>
          <li className="nav-item">
            <button className={`nav-link ${tab === 'history' ? 'active' : ''}`} onClick={() => setTab('history')}>
              Archives & Actifs
            </button>
          </li>
        </ul>

        <div className="tab-content mt-3">
          {tab === 'pending' && (
            <div className="card shadow-sm">
              <div className="card-body p-0">
                <table className="table table-hover mb-0">
                  <thead className="table-light">
                    <tr>
                      <th>#</th>
                      <th>Client</th>
                      <th>Montant demandé</th>
                      <th>Statut</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {creditsNonApprouves.length > 0 ? (
                      creditsNonApprouves.map((c) => (
                        <tr key={c.id}>
                          <td>{c.id}</td>
                          <td>{c.client ? `${c.client.nom} ${c.client.prenom}` : '—'}</td>
                          <td>{formatCurrency(c.montant_demande ?? c.montant)}</td>
                          <td>{statusLabel(c.statut ?? c.status)}</td>
                          <td>
                            <Link href={`/admin/prets/${c.id}`} className="btn btn-sm btn-primary">
                              Ouvrir le dossier
                            </Link>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="5" className="text-center py-4">
                          Aucun dossier en attente.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {tab === 'approved' && (
            <div className="card shadow-sm">
              <div className="card-body p-0">
                <table className="table table-hover mb-0">
                  <thead className="table-light">
                    <tr>
                      <th>#</th>
                      <th>Client</th>
                      <th>Montant accordé</th>
                      <th>Statut</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {creditsApprouves.length > 0 ? (
                      creditsApprouves.map((c) => (
                        <tr key={c.id}>
                          <td>{c.id}</td>
                          <td>{c.client ? `${c.client.nom} ${c.client.prenom}` : '—'}</td>
                          <td>{formatCurrency(c.montant_accorde ?? c.montant_demande ?? c.montant)}</td>
                          <td>{statusLabel(c.statut ?? c.status)}</td>
                          <td>
                            <Link href={`/admin/prets/${c.id}`} className="btn btn-sm btn-primary">
                              Ouvrir le dossier
                            </Link>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="5" className="text-center py-4">
                          Aucun dossier approuvé.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {tab === 'history' && (
            <div className="card shadow-sm">
              <div className="card-body p-0">
                <table className="table table-hover mb-0">
                  <thead className="table-light">
                    <tr>
                      <th>#</th>
                      <th>Client</th>
                      <th>Montant</th>
                      <th>Statut</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {historique.length > 0 ? (
                      historique.map((c) => (
                        <tr key={c.id}>
                          <td>{c.id}</td>
                          <td>{c.client ? `${c.client.nom} ${c.client.prenom}` : '—'}</td>
                          <td>{formatCurrency(c.montant_accorde ?? c.montant_demande ?? c.montant)}</td>
                          <td>{statusLabel(c.statut ?? c.status)}</td>
                          <td>{formatDateToFR(c.updated_at)}</td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="5" className="text-center py-4">
                          Aucun historique disponible.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
