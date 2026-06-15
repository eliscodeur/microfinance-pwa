import { Link, useForm } from '@inertiajs/inertia-react';
import { useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';
import AdminLayout from '../../Layouts/AdminLayout.jsx';
import { buildScheduleFromForm, formatCurrency, formatDateToFR } from '../../Utils/creditHelpers';

export default function Create({ clients }) {
    const form = useForm({
        client_id: '',
        carnet_id: '',
        type_support: 'compte',
        type: 'compte',
        montant_demande: 0,
        mode: 'degressif',
        periodicite: 'mensuelle',
        nombre_echeances: 3,
        taux: 1.5,
        taux_manuelle: '',
        date_debut: new Date().toISOString().slice(0, 10),
    });

    const ErrorMsg = ({ field }) => form.errors[field] ? (
        <div className="invalid-feedback d-block small">
            <i className="bi bi-exclamation-triangle-fill me-1"></i>{form.errors[field]}
        </div>
    ) : null;

    const [carnets, setCarnets] = useState([]);
    const [activeTab, setActiveTab] = useState('identification');
    const [clientSearch, setClientSearch] = useState(''); 
    
    const selectedCarnet = carnets.find(carnet => String(carnet.id) === String(form.data.carnet_id));
    
    const isCompteCarnetSelected = selectedCarnet?.type === 'compte';
    const isTontineCarnetSelected = selectedCarnet?.type === 'tontine';
    const isTypeFixedByCarnet = !!selectedCarnet;
    
    // Alerte pointage conservée et mise en évidence
    const pointageWarning = isTontineCarnetSelected && selectedCarnet?.total_pointages < selectedCarnet?.required_pointages;

    const filteredClients = useMemo(() => {
        if (!Array.isArray(clients)) return [];
        return clients.filter(client => 
            client?.carnets?.some(c => c.type === form.data.type_support)
        );
    }, [form.data.type_support, clients]);

    const availableCarnets = useMemo(() => {
        const client = clients.find(c => String(c.id) === String(form.data.client_id));
        return client ? client.carnets.filter(c => c.type === form.data.type_support) : [];
    }, [form.data.client_id, form.data.type_support, clients]);

    const handleTypeChange = (val) => {
        setClientSearch('');
        form.setData({ 
            ...form.data, 
            type_support: val, 
            client_id: '', 
            carnet_id: '',
            type: val === 'compte' ? 'compte' : 'quinzaine'
        });
    };

    useEffect(() => {
        if (isCompteCarnetSelected && form.data.type !== 'compte') {
            form.setData('type', 'compte');
        }

        if (isTontineCarnetSelected && form.data.type !== 'quinzaine') {
            form.setData('type', 'quinzaine');
        }
    }, [isCompteCarnetSelected, isTontineCarnetSelected, form.data.type]);

    useEffect(() => {
        if (!form.data.client_id) {
            setCarnets([]);
            form.setData('carnet_id', '');
            return;
        }

        const controller = new AbortController();
        const url = `/admin/carnets/get-by-client/${form.data.client_id}?t=${Date.now()}`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            signal: controller.signal,
        })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (Array.isArray(data)) {
                    setCarnets(data);
                    if (!data.some(item => String(item.id) === String(form.data.carnet_id))) {
                        form.setData('carnet_id', '');
                    }
                } else {
                    setCarnets([]);
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') setCarnets([]);
            });

        return () => controller.abort();
    }, [form.data.client_id]);

    const handleTabChange = (targetTab) => {
        // Validation renforcée pour bloquer l'accès aux onglets suivants sans carnet
        if (['details', 'simulation', 'resumes'].includes(targetTab)) {
            if (!form.data.client_id || !form.data.carnet_id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Informations manquantes',
                    text: 'Veuillez sélectionner un client et un support avant de continuer.',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }
        }

        if (targetTab === 'resumes') {
            if (!form.data.montant_demande || form.data.montant_demande <= 0 || !form.data.periodicite || !form.data.nombre_echeances || !form.data.date_debut) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Paramètres incomplets',
                    text: 'Veuillez remplir correctement les paramètres financiers.',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }
        }

        setActiveTab(targetTab);
    };

    const schedule = useMemo(() => buildScheduleFromForm(form.data), [form.data]);
    
    const [currentPage, setCurrentPage] = useState(1);
    const pageSize = 6;
    const pageCount = Math.max(1, Math.ceil(schedule.length / pageSize));

    useEffect(() => {
        setCurrentPage(1);
    }, [schedule.length]);

    const paginatedSchedule = useMemo(() => {
        const start = (currentPage - 1) * pageSize;
        return schedule.slice(start, start + pageSize);
    }, [schedule, currentPage]);

    const totalInterest = useMemo(() => schedule.reduce((sum, row) => sum + row.interest, 0), [schedule]);
    const totalDue = useMemo(() => schedule.reduce((sum, row) => sum + row.total, 0), [schedule]);
    const meanInstallment = useMemo(() => (schedule.length ? totalDue / schedule.length : 0), [schedule, totalDue]);

    const hasErrors = Object.keys(form.errors).length > 0;

    const submit = e => {
        e.preventDefault();

        Swal.fire({
            title: 'Confirmer la demande ?',
            text: 'Voulez-vous envoyer cette demande de crédit au back-office ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, envoyer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then(result => {
            if (!result.isConfirmed) return;

            form.post('/admin/credits', {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Demande envoyée',
                        text: 'La demande a bien été enregistrée.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    
                    form.reset(['montant_demande', 'type', 'mode', 'periodicite', 'nombre_echeances', 'taux', 'taux_manuelle', 'date_debut']);
                    setClientSearch(''); 
                    setActiveTab('identification');
                },
            });
        });
    };

    return (
        <AdminLayout>
            <div className="container-fluid py-4">
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 className="h3 text-primary mb-1">Nouvelle Demande de Crédit</h1>
                        <p className="text-muted mb-0">Saisie et simulation financière</p>
                    </div>
                    <Link href="/admin/credits" className="btn btn-outline-secondary">Retour</Link>
                </div>

                <form onSubmit={submit} className="card shadow-sm border-0">
                    {hasErrors && (
                        <div className="alert alert-danger m-3">
                            <i className="bi bi-exclamation-octagon-fill me-2"></i>
                            <strong>Erreur :</strong> Veuillez vérifier les champs soulignés en rouge.
                        </div>
                    )}

                    {/* NOUVELLE NAVIGATION AVEC 4 ONGLETS */}
                    <ul className="nav nav-tabs px-3 pt-3 bg-light border-bottom">
                        <li className="nav-item">
                            <button type="button" className={`nav-link fw-bold ${activeTab === 'identification' ? 'active text-primary' : 'text-muted'}`} onClick={() => handleTabChange('identification')}>
                                1. Identification
                            </button>
                        </li>
                        <li className="nav-item">
                            <button type="button" className={`nav-link fw-bold ${activeTab === 'details' ? 'active text-primary' : 'text-muted'}`} onClick={() => handleTabChange('details')}>
                                2. Détails du support
                            </button>
                        </li>
                        <li className="nav-item">
                            <button type="button" className={`nav-link fw-bold ${activeTab === 'simulation' ? 'active text-primary' : 'text-muted'}`} onClick={() => handleTabChange('simulation')}>
                                3. Simulation
                            </button>
                        </li>
                        <li className="nav-item">
                            <button type="button" className={`nav-link fw-bold ${activeTab === 'resumes' ? 'active text-primary' : 'text-muted'}`} onClick={() => handleTabChange('resumes')}>
                                4. Résumés & Échéancier
                            </button>
                        </li>
                    </ul>

                    <div className="card-body p-4">
                        
                        {/* =========================================
                            ONGLET 1 : IDENTIFICATION
                        ========================================= */}
                        {activeTab === 'identification' && (
                            <fieldset className="mb-2 animate__animated animate__fadeIn">
                                <legend className="text-uppercase h6 text-secondary border-bottom pb-2 mb-3">1. Identification</legend>
                                <div className="row g-3">
                                    <div className="col-12 mb-3">
                                        <label className="form-label fw-bold text-secondary">Type de support</label>
                                        <div className="row g-3">
                                            <div className="col-md-6">
                                                <label className={`card h-100 border-2 rounded-3 ${form.data.type_support === 'compte' ? 'border-primary bg-primary bg-opacity-10' : 'border-light shadow-sm'}`} style={{ cursor: 'pointer' }}>
                                                    <input type="radio" className="btn-check" name="type_support" checked={form.data.type_support === 'compte'} onChange={() => handleTypeChange('compte')} />
                                                    <div className="card-body d-flex align-items-center p-3">
                                                        <div className={`rounded-circle p-3 me-3 ${form.data.type_support === 'compte' ? 'bg-primary text-white' : 'bg-light text-muted'}`}>
                                                            <i className="bi bi-piggy-bank fs-4"></i>
                                                        </div>
                                                        <div>
                                                            <h6 className={`mb-0 fw-bold ${form.data.type_support === 'compte' ? 'text-primary' : 'text-dark'}`}>Compte Épargne</h6>
                                                            <small className="text-muted">Crédit basé sur un compte épargne</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>

                                            <div className="col-md-6">
                                                <label className={`card h-100 border-2 rounded-3 ${form.data.type_support === 'tontine' ? 'border-primary bg-primary bg-opacity-10' : 'border-light shadow-sm'}`} style={{ cursor: 'pointer' }}>
                                                    <input type="radio" className="btn-check" name="type_support" checked={form.data.type_support === 'tontine'} onChange={() => handleTypeChange('tontine')} />
                                                    <div className="card-body d-flex align-items-center p-3">
                                                        <div className={`rounded-circle p-3 me-3 ${form.data.type_support === 'tontine' ? 'bg-primary text-white' : 'bg-light text-muted'}`}>
                                                            <i className="bi bi-wallet2 fs-4"></i>
                                                        </div>
                                                        <div>
                                                            <h6 className={`mb-0 fw-bold ${form.data.type_support === 'tontine' ? 'text-primary' : 'text-dark'}`}>Tontine</h6>
                                                            <small className="text-muted">Crédit basé sur un carnet de tontine</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="col-md-6">
                                        <label className="form-label">Client</label>
                                        <input list="clients" className={`form-control ${form.errors.client_id ? 'is-invalid' : ''}`} placeholder="Rechercher un client..." value={clientSearch} onChange={e => {
                                                setClientSearch(e.target.value);
                                                const c = clients.find(x => `${x.nom} ${x.prenom}` === e.target.value);
                                                form.setData('client_id', c ? c.id : '');
                                            }} />
                                        <datalist id="clients">{filteredClients.map(c => <option key={c.id} value={`${c.nom} ${c.prenom}`} />)}</datalist>
                                        <ErrorMsg field="client_id" />
                                    </div>

                                    <div className="col-md-6">
                                        <label className="form-label">Support (Numéro)</label>
                                        <select className={`form-select ${form.errors.carnet_id ? 'is-invalid' : ''}`} value={form.data.carnet_id} onChange={e => form.setData('carnet_id', e.target.value)}>
                                            <option value="">Sélectionner un support</option>
                                            {availableCarnets.map(c => <option key={c.id} value={c.id}>N° {c.numero}</option>)}
                                        </select>
                                        <ErrorMsg field="carnet_id" />
                                    </div>
                                </div>

                                <div className="text-end mt-4 pt-3 border-top">
                                    <button type="button" className="btn btn-primary px-4" onClick={() => handleTabChange('details')}>
                                        Vérifier le support <i className="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </fieldset>
                        )}

                        {/* =========================================
                            ONGLET 2 : DÉTAILS DU SUPPORT (NOUVEAU)
                        ========================================= */}
                        {activeTab === 'details' && (
                            <div className="animate__animated animate__fadeIn">
                                <legend className="text-uppercase h6 text-secondary border-bottom pb-2 mb-4">
                                    2. État du support de garantie
                                </legend>

                                {selectedCarnet && (
                                    <div className="card bg-light border-0 shadow-sm mb-4">
                                        <div className="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                            <h5 className="mb-0 text-primary">
                                                <i className={`bi ${isCompteCarnetSelected ? 'bi-piggy-bank' : 'bi-wallet2'} me-2`}></i>
                                                {isCompteCarnetSelected ? 'Compte Épargne' : 'Carnet de Tontine'} N° {selectedCarnet.numero}
                                            </h5>
                                            <span className={`badge ${selectedCarnet.statut === 'actif' ? 'bg-success' : 'bg-secondary'}`}>
                                                {selectedCarnet.statut ? selectedCarnet.statut.toUpperCase() : 'ACTIF'}
                                            </span>
                                        </div>
                                        <div className="card-body row g-4">
                                            
                                            {/* Détails Communs */}
                                            <div className="col-md-4">
                                                <div className="text-muted small mb-1">Date d'ouverture</div>
                                                <div className="fw-bold">{formatDateToFR(selectedCarnet.date_creation || selectedCarnet.created_at)}</div>
                                            </div>

                                            {/* Détails spécifiques TONTINE */}
                                            {isTontineCarnetSelected && (
                                                <>
                                                    <div className="col-md-4">
                                                        <div className="text-muted small mb-1">Mise unitaire</div>
                                                        <div className="fw-bold text-dark">{formatCurrency(selectedCarnet.mise)}</div>
                                                    </div>
                                                    <div className="col-md-4">
                                                        <div className="text-muted small mb-1">Solde disponible (Base de remboursement)</div>
                                                        <div className="fw-bold text-success fs-5">{formatCurrency(selectedCarnet?.solde_tontine)}</div>
                                                    </div>
                                                  
                                                    <div className="col-md-6">
                                                        <div className="text-muted small mb-1">Progression des pointages</div>
                                                        <div className="d-flex align-items-center">
                                                            <div className="fw-bold me-2">{selectedCarnet.total_pointages || 0} / {selectedCarnet.required_pointages || 0}</div>
                                                            <div className="progress flex-grow-1" style={{ height: '8px' }}>
                                                                <div className={`progress-bar ${pointageWarning ? 'bg-warning' : 'bg-success'}`} 
                                                                     role="progressbar" 
                                                                     style={{ width: `${Math.min(100, ((selectedCarnet.total_pointages || 0) / (selectedCarnet.required_pointages || 1)) * 100)}%` }}>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="col-md-6">
                                                        <div className="text-muted small mb-1">Date fin théorique du cycle</div>
                                                        <div className="fw-bold">{formatDateToFR(selectedCarnet.date_fin_cycle) || 'Non définie'}</div>
                                                    </div>
                                                </>
                                            )}

                                            {/* Détails spécifiques COMPTE ÉPARGNE */}
                                            {isCompteCarnetSelected && (
                                                <>
                                                    <div className="col-md-4">
                                                        <div className="text-muted small mb-1">Solde disponible</div>
                                                        <div className="fw-bold text-success fs-5">{formatCurrency(selectedCarnet?.solde)}</div>
                                                    </div>
                                                    <div className="col-md-4">
                                                        <div className="text-muted small mb-1">Solde bloqué (Garanties)</div>
                                                        <div className="fw-bold text-danger">{formatCurrency(selectedCarnet?.solde_bloque || 0)}</div>
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* L'alerte se déclenche ici de façon bien visible */}
                                {pointageWarning && (
                                    <div className="alert alert-warning shadow-sm d-flex align-items-center">
                                        <i className="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
                                        <div>
                                            <strong>Pointages insuffisants :</strong> Ce carnet n'a pas atteint le minimum requis pour prétendre à un octroi standard. Une validation managériale pourrait être nécessaire.
                                        </div>
                                    </div>
                                )}

                                <div className="d-flex justify-content-between pt-3 border-top mt-4">
                                    <button type="button" className="btn btn-link text-secondary text-decoration-none p-0" onClick={() => handleTabChange('identification')}>
                                        <i className="bi bi-arrow-left me-1"></i> Modifier l'identification
                                    </button>
                                    <button type="button" className="btn btn-primary px-4" onClick={() => handleTabChange('simulation')}>
                                        Étape 3 : Paramètres financiers <i className="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* =========================================
                            ONGLET 3 : SIMULATION
                        ========================================= */}
                        {activeTab === 'simulation' && (
                            <div className="animate__animated animate__fadeIn">
                                <fieldset className="mb-4">
                                    <legend className="text-uppercase h6 text-secondary border-bottom pb-2 mb-3">3. Paramètres financiers</legend>
                                    <div className="row g-3">
                                        <div className="col-md-6">
                                            <label className="form-label">Montant (FCFA)</label>
                                            <input type="number" className={`form-control ${form.errors.montant_demande ? 'is-invalid' : ''}`} value={form.data.montant_demande} onChange={e => form.setData('montant_demande', e.target.value)} />
                                            <ErrorMsg field="montant_demande" />
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Type de crédit</label>
                                            <select className="form-select" value={form.data.type} onChange={e => form.setData('type', e.target.value)} required disabled={isTypeFixedByCarnet}>
                                                {selectedCarnet?.type === 'compte' ? <option value="compte">Crédit sur compte</option> : selectedCarnet?.type === 'tontine' ? <option value="quinzaine">Crédit quinzaine</option> : (
                                                    <><option value="">Choisir</option><option value="compte">Crédit sur compte</option><option value="quinzaine">Crédit quinzaine</option><option value="mensuel">Crédit mensuel</option></>
                                                )}
                                            </select>
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Périodicité</label>
                                            <select className="form-select" value={form.data.periodicite} onChange={e => form.setData('periodicite', e.target.value)} required>
                                                <option value="">Choisir</option>
                                                <option value="quinzaine">Quinzaine</option>
                                                <option value="mensuelle">Mensuelle</option>
                                            </select>
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Nombre d'échéances</label>
                                            <input type="number" className="form-control" value={form.data.nombre_echeances} onChange={e => form.setData('nombre_echeances', e.target.value)} />
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Date de début</label>
                                            <input type="date" className="form-control" value={form.data.date_debut} onChange={e => form.setData('date_debut', e.target.value)} />
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Mode de calcul</label>
                                            <select className="form-select" value={form.data.mode} onChange={e => form.setData('mode', e.target.value)}>
                                                <option value="fixe">Fixe</option>
                                                <option value="degressif">Dégressif</option>
                                            </select>
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Taux standard (%)</label>
                                            <input type="number" className="form-control" value={form.data.taux} onChange={e => form.setData('taux', e.target.value)} />
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label text-warning">Taux manuel (%)</label>
                                            <input type="number" className="form-control border-warning" value={form.data.taux_manuelle} onChange={e => form.setData('taux_manuelle', e.target.value)} />
                                        </div>
                                    </div>
                                </fieldset>

                                <div className="d-flex justify-content-between pt-3 border-top mt-4">
                                    <button type="button" className="btn btn-link text-secondary text-decoration-none p-0" onClick={() => handleTabChange('details')}>
                                        <i className="bi bi-arrow-left me-1"></i> Retour aux détails
                                    </button>
                                    <button type="button" className="btn btn-primary px-4" onClick={() => handleTabChange('resumes')}>
                                        Étape 4 : Échéancier <i className="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* =========================================
                            ONGLET 4 : RÉSUMÉS FINANCIERS & ÉCHÉANCIER
                        ========================================= */}
                        {activeTab === 'resumes' && (
                            <div className="animate__animated animate__fadeIn">
                                <fieldset className="mb-4">
                                    <legend className="h6 text-uppercase text-secondary border-bottom pb-2 mb-3">4. Résumés financiers & Échéancier</legend>
                                    
                                    <div className="row gy-3 mb-4">
                                        <div className="col-md-4">
                                            <div className="border rounded-3 p-3 bg-light">
                                                <div className="text-muted small">Montant total à rembourser</div>
                                                <div className="fs-4 fw-bold text-dark">{formatCurrency(totalDue)}</div>
                                            </div>
                                        </div>
                                        <div className="col-md-4">
                                            <div className="border rounded-3 p-3 bg-light">
                                                <div className="text-muted small">Intérêt total</div>
                                                <div className="fs-4 fw-bold text-dark">{formatCurrency(totalInterest)}</div>
                                            </div>
                                        </div>
                                        <div className="col-md-4">
                                            <div className="border rounded-3 p-3 bg-light">
                                                <div className="text-muted small">Échéance moyenne</div>
                                                <div className="fs-4 fw-bold text-dark">{formatCurrency(meanInstallment)}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="table-responsive">
                                        <table className="table table-sm mt-2">
                                            <thead><tr><th>Date</th><th>Principal</th><th>Intérêt</th><th>Total</th></tr></thead>
                                            <tbody>
                                                {paginatedSchedule.map((s, i) => (
                                                    <tr key={i}>
                                                        <td>{formatDateToFR(s.date)}</td>
                                                        <td>{formatCurrency(s.principal)}</td>
                                                        <td>{formatCurrency(s.interest)}</td>
                                                        <td><strong>{formatCurrency(s.total)}</strong></td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    {schedule.length > pageSize && (
                                        <div className="d-flex justify-content-between align-items-center mt-2">
                                            <span className="text-muted small">Page {currentPage} sur {pageCount}</span>
                                            <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setCurrentPage(p => p < pageCount ? p + 1 : 1)}>
                                                Page suivante <i className="bi bi-chevron-right small"></i>
                                            </button>
                                        </div>
                                    )}
                                </fieldset>

                                <div className="d-flex justify-content-between pt-3 border-top mt-4">
                                    <button type="button" className="btn btn-link text-secondary text-decoration-none p-0" onClick={() => handleTabChange('simulation')}>
                                        <i className="bi bi-arrow-left me-1"></i> Retour à la simulation
                                    </button>
                                    <button type="submit" className="btn btn-success px-5 shadow-sm" disabled={form.processing}>
                                        {form.processing ? 'Chargement...' : 'Enregistrer la demande'}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}