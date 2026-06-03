import React, { useState, useMemo } from 'react';
import { Link } from '@inertiajs/inertia-react';
import AdminLayout from '../../Layouts/AdminLayout.jsx';

// Badges de statut professionnels et soft
const renderStatusBadge = statut => {
  switch (statut) {
    case 'pending':
    case 'soumis':
    case 'en_etude':
      return <span className="badge bg-secondary-subtle text-secondary border px-2.5 py-1.5 fw-medium">En étude</span>;
    case 'approved':
    case 'approuve':
      return <span className="badge bg-success-subtle text-success border px-2.5 py-1.5 fw-medium">Approuvé</span>;
    case 'active':
      return <span className="badge bg-primary-subtle text-primary border px-2.5 py-1.5 fw-medium">Actif</span>;
    case 'in_arrears':
      return <span className="badge bg-danger-subtle text-danger border px-2.5 py-1.5 fw-medium">En retard</span>;
    case 'solder':
    case 'solde':
      return <span className="badge bg-info-subtle text-info border px-2.5 py-1.5 fw-medium">Soldé</span>;
    case 'rejected':
    case 'rejete':
      return <span className="badge bg-light text-muted border px-2.5 py-1.5 fw-medium">Rejeté</span>;
    default:
      return <span className="badge bg-light text-dark border px-2.5 py-1.5 fw-medium">{statut}</span>;
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
  if (!value) return '—';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;

  return parsed.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

export default function Index(props) {
  const { creditsNonApprouves = [], creditsApprouves = [], historique = [] } = props;
  const [tab, setTab] = useState('pending');
  const [searchQuery, setSearchQuery] = useState('');

  // Filtrage local simple et propre par nom ou prénom de client
  const filterByClientName = (list) => {
    return list.filter(c => {
      const fullname = c.client ? `${c.client.nom} ${c.client.prenom}`.toLowerCase() : '';
      return fullname.includes(searchQuery.toLowerCase());
    });
  };

  const filteredNonApprouves = useMemo(() => filterByClientName(creditsNonApprouves), [creditsNonApprouves, searchQuery]);
  const filteredApprouves = useMemo(() => filterByClientName(creditsApprouves), [creditsApprouves, searchQuery]);
  const filteredHistorique = useMemo(() => filterByClientName(historique), [historique, searchQuery]);

  return (
    <AdminLayout>
      <div className="container-fluid px-4 py-3 bg-light-subtle">
        
        {/* En-tête épuré avec barre de recherche intégrée */}
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
          <div>
            <h1 className="h4 mb-1 text-dark fw-bold">Gestion des dossiers de crédit</h1>
            <p className="text-muted small mb-0">Suivi des demandes, arbitrage des dossiers et archivage des flux actifs.</p>
          </div>
          <div style={{ maxWidth: '300px' }} className="w-100">
            <div className="input-group input-group-sm">
              <span className="input-group-text bg-white text-muted border-end-0">
                <i className="bi bi-search"></i>
              </span>
              <input 
                type="text" 
                className="form-control form-control-sm border-start-0 ps-0 text-muted" 
                placeholder="Rechercher un client..." 
                value={searchQuery}
                onChange={e => setSearchQuery(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* Navigation par Onglets Soft */}
        <ul className="nav nav-tabs border-bottom-0 small mb-3 gap-1">
          <li className="nav-item">
            <button 
              className={`nav-link border-0 rounded-3 px-3 py-2 fw-medium ${tab === 'pending' ? 'bg-white shadow-sm text-dark border-bottom-0' : 'text-muted bg-transparent'}`} 
              onClick={() => setTab('pending')}
            >
              En attente d'approbation
            </button>
          </li>
          <li className="nav-item">
            <button 
              className={`nav-link border-0 rounded-3 px-3 py-2 fw-medium ${tab === 'approved' ? 'bg-white shadow-sm text-dark border-bottom-0' : 'text-muted bg-transparent'}`} 
              onClick={() => setTab('approved')}
            >
              Approuvés / En attente de fonds
            </button>
          </li>
          <li className="nav-item">
            <button 
              className={`nav-link border-0 rounded-3 px-3 py-2 fw-medium ${tab === 'history' ? 'bg-white shadow-sm text-dark border-bottom-0' : 'text-muted bg-transparent'}`} 
              onClick={() => setTab('history')}
            >
              Archives &amp; Actifs
            </button>
          </li>
        </ul>

        {/* Contenu des dossiers */}
        <div className="tab-content">
          
          {/* 1. Onglet En attente */}
          {tab === 'pending' && (
            <div className="card shadow-sm border-light rounded-3 overflow-hidden">
              <div className="table-responsive" style={{ maxHeight: '500px' }}>
                <table className="table table-sm table-hover align-middle mb-0 text-secondary" style={{ fontSize: '13px' }}>
                  <thead className="table-light sticky-top">
                    <tr className="text-muted text-uppercase" style={{ fontSize: '11px', trackingMultiplier: 1 }}>
                      <th className="ps-3 py-2.5 text-start" style={{ width: '80px' }}>#</th>
                      <th className="text-start">Client</th>
                      <th className="text-end" style={{ width: '180px' }}>Montant demandé</th>
                      <th className="text-start ps-4">Conditions</th>
                      <th className="text-center" style={{ width: '150px' }}>Statut</th>
                      <th className="text-end pe-3" style={{ width: '140px' }}>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredNonApprouves.length > 0 ? (
                      filteredNonApprouves.map((c) => (
                        <tr key={c.id}>
                          <td className="ps-3 fw-semibold text-dark">#{c.id}</td>
                          <td className="text-dark fw-medium">{c.client ? `${c.client.nom} ${c.client.prenom}` : '—'}</td>
                          <td className="text-end font-monospace fw-semibold text-dark">{formatCurrency(c.montant_demande ?? c.montant)}</td>
                          <td className="ps-4 text-capitalize text-muted">
                            {c.periodicite ?? '—'} • {c.nombre_echeances ?? '—'} échs • {c.mode ?? '—'}
                          </td>
                          <td className="text-center">{renderStatusBadge(c.statut ?? c.status)}</td>
                          <td className="text-end pe-3">
                            <Link href={`/admin/prets/${c.id}`} className="btn btn-sm btn-outline-secondary px-2 py-1" style={{ fontSize: '12px' }}>
                              Arbitrer <i className="bi bi-chevron-right ms-1"></i>
                            </Link>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="6" className="text-center text-muted py-4 small">Aucun dossier en attente d'approbation.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 2. Onglet Approuvés */}
          {tab === 'approved' && (
            <div className="card shadow-sm border-light rounded-3 overflow-hidden">
              <div className="table-responsive" style={{ maxHeight: '500px' }}>
                <table className="table table-sm table-hover align-middle mb-0 text-secondary" style={{ fontSize: '13px' }}>
                  <thead className="table-light sticky-top">
                    <tr className="text-muted text-uppercase" style={{ fontSize: '11px' }}>
                      <th className="ps-3 py-2.5 text-start" style={{ width: '80px' }}>#</th>
                      <th className="text-start">Client</th>
                      <th className="text-end" style={{ width: '180px' }}>Montant accordé</th>
                      <th className="text-start ps-4">Conditions d'octroi</th>
                      <th className="text-center" style={{ width: '150px' }}>Statut</th>
                      <th className="text-end pe-3" style={{ width: '140px' }}>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredApprouves.length > 0 ? (
                      filteredApprouves.map((c) => (
                        <tr key={c.id}>
                          <td className="ps-3 fw-semibold text-dark">#{c.id}</td>
                          <td className="text-dark fw-medium">{c.client ? `${c.client.nom} ${c.client.prenom}` : '—'}</td>
                          <td className="text-end font-monospace fw-bold text-success">{formatCurrency(c.montant_accorde ?? c.montant_demande ?? c.montant)}</td>
                          <td className="ps-4 text-capitalize text-muted">
                            {c.periodicite ?? '—'} • {c.nombre_echeances ?? '—'} échs • {c.mode ?? '—'}
                          </td>
                          <td className="text-center">{renderStatusBadge(c.statut ?? c.status)}</td>
                          <td className="text-end pe-3">
                            <Link href={`/admin/prets/${c.id}`} className="btn btn-sm btn-outline-primary px-2 py-1" style={{ fontSize: '12px' }}>
                              Décaissement <i className="bi bi-chevron-right ms-1"></i>
                            </Link>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="6" className="text-center text-muted py-4 small">Aucun dossier approuvé en attente de fonds.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 3. Onglet Historique / Archives */}
          {tab === 'history' && (
            <div className="card shadow-sm border-light rounded-3 overflow-hidden">
              <div className="table-responsive" style={{ maxHeight: '500px' }}>
                <table className="table table-sm table-hover align-middle mb-0 text-secondary" style={{ fontSize: '13px' }}>
                  <thead className="table-light sticky-top">
                    <tr className="text-muted text-uppercase" style={{ fontSize: '11px' }}>
                      <th className="ps-3 py-2.5 text-start" style={{ width: '80px' }}>#</th>
                      <th className="text-start">Client</th>
                      <th className="text-end" style={{ width: '180px' }}>Montant final</th>
                      <th className="text-start ps-4">Conditions</th>
                      <th className="text-center" style={{ width: '150px' }}>Statut</th>
                      <th className="text-end pe-3" style={{ width: '140px' }}>Dernière mise à jour</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredHistorique.length > 0 ? (
                      filteredHistorique.map((c) => (
                        <tr key={c.id}>
                          <td className="ps-3 fw-semibold text-muted">#{c.id}</td>
                          <td className="text-dark">{c.client ? `${c.client.nom} ${c.client.prenom}` : '—'}</td>
                          <td className="text-end font-monospace fw-semibold text-dark">{formatCurrency(c.montant_accorde ?? c.montant_demande ?? c.montant)}</td>
                          <td className="ps-4 text-capitalize text-muted">
                            {c.periodicite ?? '—'} • {c.nombre_echeances ?? '—'} échs • {c.mode ?? '—'}
                          </td>
                          <td className="text-center">{renderStatusBadge(c.statut ?? c.status)}</td>
                          <td className="text-end pe-3 font-monospace text-muted" style={{ fontSize: '12px' }}>
                            {formatDateToFR(c.updated_at)}
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="6" className="text-center text-muted py-4 small">Aucun historique disponible dans cette section.</td>
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