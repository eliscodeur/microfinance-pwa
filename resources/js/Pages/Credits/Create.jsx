import { Link, useForm } from '@inertiajs/inertia-react';
import { useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';
import AdminLayout from '../../Layouts/AdminLayout.jsx';
import { buildScheduleFromForm, formatCurrency, formatDateToFR } from '../../Utils/creditHelpers';

export default function Create({ clients, creditProducts = [] }) {
    const form = useForm({
        // 1. Données de contexte / sélection du client
        client_id: '',
        carnet_id: '',
        type_support: 'compte', // 'compte' ou 'tontine'
        
        // 2. Configuration et détails du crédit
        credit_type_id: '',      
        credit_product_id: '',   
        credit_object_id: '',    
        montant_demande: 0,      
        
        // 3. Échéancier et tarification
        periodicite: 'mensuelle', 
        nombre_echeances: 5,     
        date_debut: new Date().toISOString().slice(0, 10), 
        differe: 0,              
        frais_dossier: '',       
        garantie: '',            
        mode: 'degressif',       
        taux: 1.5,               
        taux_manuelle: '',       

        // 4.1 Caution Solidaire / Avaliste
        garant_nom_prenom: '',   // Nom & Prénoms du garant
        garant_telephone: '',    // Numéro de Téléphone
        garant_profession: '',   // Profession / Secteur d'activité
        garant_adresse: '',      // Quartier de résidence

        // 4.2 Documents & KYC d'Audit (Initialisés à null pour la gestion des fichiers)
        piece_identite: null,       // Fichier : CNIB, Passeport ou Carte d'Électeur
        justificatif_revenu: null,  // Fichier : Facture CEET/TdE, Plan ou Carte CFE
    });

    const ErrorMsg = ({ field }) => form.errors[field] ? (
        <div className="invalid-feedback d-block small">
            <i className="bi bi-exclamation-triangle-fill me-1"></i>{form.errors[field]}
        </div>
    ) : null;

    const [carnets, setCarnets] = useState([]);
    const [activeTab, setActiveTab] = useState('identification');
    const [clientSearch, setClientSearch] = useState('');
    const [carnetDetails, setCarnetDetails] = useState(null);
    const [loadingDetails, setLoadingDetails] = useState(false); 
    
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

    // 1. Filtrer les produits selon le type de carnet requis (tontine/compte)
    const filteredProducts = useMemo(() => {
        if (!form.data.type_support) return [];
        return creditProducts.filter(p => p.type_carnet_requis === form.data.type_support);
    }, [form.data.type_support, creditProducts]);

    // 2. Récupérer l'objet complet du produit sélectionné
    const selectedProduct = useMemo(() => {
        return filteredProducts.find(p => String(p.id) === String(form.data.credit_product_id)) || null;
    }, [form.data.credit_product_id, filteredProducts]);

    // 3. Extraire les objets de crédit autorisés pour ce produit (via la table pivot)
    const availableObjects = useMemo(() => {
        return selectedProduct ? (selectedProduct.credit_objects || []) : [];
    }, [selectedProduct]);
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
        if (!form.data.credit_product_id || creditProducts.length === 0) return;

        if (selectedProduct) {
            form.setData({
                ...form.data,
                // Mappage sur les colonnes réelles de ton seeder (image_800cc5.png)
                taux: selectedProduct.taux_interet_defaut, 
                frais_dossier: selectedProduct.frais_dossier_defaut,
                nombre_echeances: form.data.nombre_echeances || selectedProduct.duree_max_mois,
                
                // Gestion du type de crédit selon le support
                type: form.data.type_support === 'compte' ? 'compte' : (form.data.type || 'quinzaine'),
                periodicite: form.data.type_support === 'tontine' ? 'quinzaine' : 'mensuelle'
            });
        }
    }, [form.data.credit_product_id, selectedProduct]);

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

    // Fetch carnet details when carnet_id changes
    useEffect(() => {
        if (!form.data.carnet_id || !selectedCarnet) {
            setCarnetDetails(null);
            return;
        }

        setLoadingDetails(true);
        const controller = new AbortController();
        const url = `/admin/carnets/details/${form.data.carnet_id}`;

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
                if (data.success) {
                    setCarnetDetails(data);
                }
                setLoadingDetails(false);
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    console.error('Error fetching carnet details:', err);
                    setCarnetDetails(null);
                }
                setLoadingDetails(false);
            });

        return () => controller.abort();
    }, [form.data.carnet_id, selectedCarnet]);

    const handleTabChange = (targetTab) => {
        // Validation renforcée pour bloquer l'accès aux onglets suivants sans carnet
        if (['details', 'simulation', 'resumes', 'garanties'].includes(targetTab)) {
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

        if (['garanties', 'resumes'].includes(targetTab)) {
            if (!form.data.credit_product_id || !form.data.montant_demande || form.data.montant_demande <= 0 || !form.data.periodicite || !form.data.nombre_echeances || !form.data.date_debut || !form.data.objet_credit || !form.data.credit_product_id || form.data.periodicite === '' || form.data.nombre_echeances <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Simulation incomplète',
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
                    <ul className="nav nav-tabs px-3 pt-3 bg-white border-bottom flex-nowrap overflow-auto shadow-sm">
                        {/* Étape 1 : Identification */}
                        <li className="nav-item">
                            <button 
                                type="button" 
                                className={`nav-link pt-2 pb-3 fw-bold d-flex align-items-center gap-2 border-0 ${activeTab === 'identification' ? 'active text-primary border-bottom border-primary border-2 bg-transparent' : 'text-muted'}`} 
                                onClick={() => handleTabChange('identification')}
                            >
                                <small 
                                    className={`badge rounded-circle d-flex align-items-center justify-content-center p-0 ${activeTab === 'identification' ? 'bg-primary text-white' : 'bg-light text-secondary border'}`} 
                                    style={{ width: '22px', height: '22px', fontSize: '11px' }}
                                >
                                    1
                                </small>
                                <span>Identification Client</span>
                            </button>
                        </li>

                        {/* Étape 2 : Support & Produit */}
                        <li className="nav-item">
                            <button 
                                type="button" 
                                className={`nav-link pt-2 pb-3 fw-bold d-flex align-items-center gap-2 border-0 ${activeTab === 'details' ? 'active text-primary border-bottom border-primary border-2 bg-transparent' : 'text-muted'}`} 
                                onClick={() => handleTabChange('details')}
                            >
                                <small 
                                    className={`badge rounded-circle d-flex align-items-center justify-content-center p-0 ${activeTab === 'details' ? 'bg-primary text-white' : 'bg-light text-secondary border'}`} 
                                    style={{ width: '22px', height: '22px', fontSize: '11px' }}
                                >
                                    2
                                </small>
                                <span>Support &amp; Produit</span>
                            </button>
                        </li>

                        {/* Étape 3 : Simulation du Prêt */}
                        <li className="nav-item">
                            <button 
                                type="button" 
                                className={`nav-link pt-2 pb-3 fw-bold d-flex align-items-center gap-2 border-0 ${activeTab === 'simulation' ? 'active text-primary border-bottom border-primary border-2 bg-transparent' : 'text-muted'}`} 
                                onClick={() => handleTabChange('simulation')}
                            >
                                <small 
                                    className={`badge rounded-circle d-flex align-items-center justify-content-center p-0 ${activeTab === 'simulation' ? 'bg-primary text-white' : 'bg-light text-secondary border'}`} 
                                    style={{ width: '22px', height: '22px', fontSize: '11px' }}
                                >
                                    3
                                </small>
                                <span>Simulation du Prêt</span>
                            </button>
                        </li>

                        {/* Étape 4 : Plan d'Amortissement */}
                        <li className="nav-item">
                            <button 
                                type="button" 
                                className={`nav-link pt-2 pb-3 fw-bold d-flex align-items-center gap-2 border-0 ${activeTab === 'resumes' ? 'active text-primary border-bottom border-primary border-2 bg-transparent' : 'text-muted'}`} 
                                onClick={() => handleTabChange('resumes')}
                            >
                                <small 
                                    className={`badge rounded-circle d-flex align-items-center justify-content-center p-0 ${activeTab === 'resumes' ? 'bg-primary text-white' : 'bg-light text-secondary border'}`} 
                                    style={{ width: '22px', height: '22px', fontSize: '11px' }}
                                >
                                    4
                                </small>
                                <span>Plan d'Amortissement</span>
                            </button>
                        </li>

                        {/* Étape 5 : Garanties & KYC */}
                        <li className="nav-item">
                            <button 
                                type="button" 
                                className={`nav-link pt-2 pb-3 fw-bold d-flex align-items-center gap-2 border-0 ${activeTab === 'garanties' ? 'active text-primary border-bottom border-primary border-2 bg-transparent' : 'text-muted'}`} 
                                onClick={() => handleTabChange('garanties')}
                            >
                                <small 
                                    className={`badge rounded-circle d-flex align-items-center justify-content-center p-0 ${activeTab === 'garanties' ? 'bg-primary text-white' : 'bg-light text-secondary border'}`} 
                                    style={{ width: '22px', height: '22px', fontSize: '11px' }}
                                >
                                    5
                                </small>
                                <span>Garanties &amp; KYC</span>
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

                                {loadingDetails && (
                                    <div className="text-center py-5">
                                        <div className="spinner-border text-primary" role="status">
                                            <span className="visually-hidden">Chargement...</span>
                                        </div>
                                        <p className="text-muted mt-2">Chargement des détails du carnet...</p>
                                    </div>
                                )}

                                {carnetDetails && carnetDetails.type === 'tontine' && carnetDetails.cycles && (
                                    <div className="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
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
                                                        N° {selectedCarnet.numero}
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                {/* Badge de compteur adouci */}
                                                <span 
                                                    className="badge bg-light text-secondary border fw-medium rounded-pill px-3 py-2" 
                                                    style={{ fontSize: '0.75rem' }}
                                                >
                                                    {carnetDetails.cycles.length} Cycle{carnetDetails.cycles.length > 1 ? 's' : ''}
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
                                                    {carnetDetails.cycles.map((cycle, idx) => (
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
                                                            
                                                            {/* Mise : retrait du badge bleu flashy, on laisse le texte propre */}
                                                            <td className="text-center fw-semibold text-dark" style={{ fontSize: '0.85rem' }}>
                                                                {formatCurrency(cycle.mise)}
                                                            </td>
                                                            
                                                            <td className="text-center text-muted" style={{ fontSize: '0.85rem' }}>
                                                                {cycle.total_pointages}
                                                            </td>
                                                            
                                                            {/* Statut : Badges transparents (opacity-10) et arrondis (pill) */}
                                                            <td className="text-center">
                                                                <span 
                                                                    className={`badge rounded-pill fw-medium ${cycle.statut === 'termine' ? 'bg-success bg-opacity-10 text-success' : cycle.statut === 'en_cours' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-secondary bg-opacity-10 text-secondary'}`} 
                                                                    style={{ fontSize: '0.75rem' }}
                                                                >
                                                                    {cycle.statut === 'en_cours' ? 'En cours' : cycle.statut === 'termine' ? 'Terminé' : cycle.statut}
                                                                </span>
                                                            </td>
                                                            
                                                            {/* Retard : Badges transparents également */}
                                                            <td className="text-center pe-4">
                                                                <span 
                                                                    className={`badge rounded-pill fw-medium ${cycle.en_retard ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success'}`} 
                                                                    style={{ fontSize: '0.75rem' }}
                                                                >
                                                                    {cycle.en_retard ? 'En retard' : 'À jour'}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                {carnetDetails && carnetDetails.type === 'compte' && (
                                    <div>
                                        {/* En-tête Compte */}
                                        <div className="card border-0 shadow-sm mb-4 rounded-4">
                                            <div className="card-body p-3 p-md-4">
                                                {/* En-tête : Type de compte et Numéro */}
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
                                                            N° {selectedCarnet.numero}
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Corps : Solde disponible */}
                                                <div className="mt-2">
                                                    <div 
                                                        className="text-muted text-uppercase fw-semibold mb-1" 
                                                        style={{ fontSize: '0.65rem', letterSpacing: '0.5px' }}
                                                    >
                                                        Solde disponible
                                                    </div>
                                                    <div className="fw-bolder text-dark" style={{ fontSize: '1.75rem', lineHeight: '1' }}>
                                                        {formatCurrency(carnetDetails.solde)}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Historique des Transactions */}
                                        <div className="card border-0 shadow-sm">
                                            <div className="card-header bg-light border-bottom pt-3 pb-2">
                                                <h6 className="mb-0 text-secondary fw-bold">10 Derniers mouvements</h6>
                                            </div>
                                            <div className="card-body p-0">
                                                {carnetDetails.historique && carnetDetails.historique.length > 0 ? (
                                                    <div className="list-group list-group-flush">
                                                        {carnetDetails.historique.map((transaction, idx) => (
                                                            <div key={idx} className="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-light">
                                                                
                                                                <div className="d-flex align-items-center gap-2 flex-grow-1">
                                                                    {/* Icône adoucie et plus petite */}
                                                                    <div 
                                                                        className={`d-flex align-items-center justify-content-center rounded ${transaction.type_transaction === 'Dépôt' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger'}`} 
                                                                        style={{ width: '32px', height: '32px' }}
                                                                    >
                                                                        <i className={`bi ${transaction.type_transaction === 'Dépôt' ? 'bi-arrow-down-short' : 'bi-arrow-up-short'} fs-5`}></i>
                                                                    </div>
                                                                    
                                                                    {/* Détails du texte plus compacts */}
                                                                    <div className="lh-sm">
                                                                        <div className="fw-medium text-dark" style={{ fontSize: '0.85rem' }}>
                                                                            {transaction.type_transaction}
                                                                        </div>
                                                                        <small className="text-muted" style={{ fontSize: '0.70rem' }}>
                                                                            {transaction.date}
                                                                        </small>
                                                                    </div>
                                                                </div>

                                                                {/* Montant aligné à droite, typographie nette */}
                                                                <div className="text-end">
                                                                    <div className={`fw-semibold ${transaction.type_transaction === 'Dépôt' ? 'text-success' : 'text-danger'}`} style={{ fontSize: '0.85rem' }}>
                                                                        {transaction.type_transaction === 'Dépôt' ? '+' : '-'} {formatCurrency(transaction.montant)}
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    /* État vide (Empty state) plus discret */
                                                    <div className="p-3 text-center text-muted">
                                                        <i className="bi bi-journal-text fs-4 opacity-50 d-block mb-1"></i>
                                                        <span style={{ fontSize: '0.8rem' }}>Aucune transaction récente</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {!loadingDetails && !carnetDetails && selectedCarnet && (
                                    <div className="alert alert-warning">
                                        <i className="bi bi-exclamation-triangle-fill me-2"></i>
                                        Impossible de charger les détails du carnet.
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
                                    <legend className="text-uppercase h6 text-primary border-bottom pb-2 mb-3">
                                        <i className="bi bi-sliders me-2"></i>3. Paramètres financiers &amp; Conditions
                                    </legend>
                                    
                                    <div className="row g-3">
                                        {/* 1. SÉLECTION DU PRODUIT DE CRÉDIT (DYNAMIQUE BDD) */}
                                        <div className="col-md-6">
                                            <label htmlFor="credit_product_id" className="form-label fw-semibold">Produit de crédit <span className="text-danger">*</span></label>
                                            <select 
                                                id="credit_product_id"
                                                className={`form-select ${form.errors.credit_product_id ? 'is-invalid' : ''}`}
                                                value={form.data.credit_product_id || ''} 
                                                onChange={e => {
                                                    form.setData({
                                                        ...form.data,
                                                        credit_product_id: e.target.value,
                                                        objet_credit: '' // Nettoie le motif pour éviter les incohérences
                                                    });
                                                }}
                                                disabled={!form.data.type_support}
                                                required
                                            >
                                                <option value="" disabled>Choisir un produit de crédit...</option>
                                                {filteredProducts.map((product) => (
                                                    <option key={product.id} value={product.id}>
                                                        {product.nom} ({product.code})
                                                    </option>
                                                ))}
                                            </select>
                                            <ErrorMsg field="credit_product_id" />
                                        </div>

                                        {/* 2. OBJET DU CRÉDIT */}
                                        <div className="col-md-6">
                                            <label htmlFor="objet_credit" className="form-label fw-semibold">Objet du crédit <span className="text-danger">*</span></label>
                                            <select 
                                                id="objet_credit"
                                                className={`form-select ${form.errors.objet_credit ? 'is-invalid' : ''}`}
                                                value={form.data.objet_credit || ''} 
                                                onChange={e => form.setData('objet_credit', e.target.value)}
                                                disabled={!form.data.credit_product_id}
                                                required
                                            >
                                                {!form.data.credit_product_id ? (
                                                    <option value="">Veuillez d'abord choisir un produit...</option>
                                                ) : (
                                                    <>
                                                        <option value="" disabled>Choisir le motif du crédit...</option>
                                                        {availableObjects.map((obj) => (
                                                            <option key={obj.id} value={obj.id}>
                                                                {obj.nom}
                                                            </option>
                                                        ))}
                                                    </>
                                                )}
                                            </select>
                                            <ErrorMsg field="objet_credit" />
                                        </div>
                                        {/* 3. MONTANT DEMANDÉ */}
                                        <div className="col-md-6">
                                            <label htmlFor="montant_demande" className="form-label fw-semibold">Montant demandé (FCFA) <span className="text-danger">*</span></label>
                                            <div className="input-group">
                                                <input 
                                                    id="montant_demande"
                                                    type="number" 
                                                    min="0"
                                                    className={`form-control ${form.errors.montant_demande ? 'is-invalid' : ''}`} 
                                                    value={form.data.montant_demande || ''} 
                                                    onChange={e => form.setData('montant_demande', e.target.value)} 
                                                    required
                                                />
                                                <span className="input-group-text bg-light fw-bold">FCFA</span>
                                            </div>
                                            <ErrorMsg field="montant_demande" />
                                        </div>

                                        {/* 4. TYPE DE CRÉDIT (Géré dynamiquement par carnet sélectionné) */}
                                        <div className="col-md-6">
                                            <label htmlFor="type_credit" className="form-label fw-semibold">Type de crédit</label>
                                            <select 
                                                id="type_credit"
                                                className={`form-select ${form.errors.type ? 'is-invalid' : ''}`} 
                                                value={form.data.type || ''} 
                                                onChange={e => form.setData('type', e.target.value)} 
                                                disabled={!form.data.credit_product_id}
                                                required 
                                            >
                                                {form.data.type_support === 'compte' && (
                                                    <option value="compte">Crédit sur compte</option>
                                                )}
                                                {form.data.type_support === 'tontine' && (
                                                    <>
                                                        <option value="" disabled>Choisir la durée...</option>
                                                        <option value="quinzaine">Crédit quinzaine</option>
                                                        <option value="mensuel">Crédit mensuel</option>
                                                    </>
                                                )}
                                            </select>
                                            <ErrorMsg field="type" />
                                        </div>
                                        {/* 5. PÉRIODICITÉ & ÉCHÉANCES */}
                                        <div className="col-md-3">
                                            <label htmlFor="periodicite" className="form-label fw-semibold">Périodicité</label>
                                            <select id="periodicite" className="form-select" value={form.data.periodicite || ''} onChange={e => form.setData('periodicite', e.target.value)} required>
                                                <option value="" disabled>Choisir</option>
                                                <option value="quinzaine">Quinzaine</option>
                                                <option value="mensuelle">Mensuelle</option>
                                            </select>
                                        </div>

                                        <div className="col-md-3">
                                            <label htmlFor="nombre_echeances" className="form-label fw-semibold">Nb échéances</label>
                                            <input id="nombre_echeances" type="number" min="1" className="form-control" value={form.data.nombre_echeances || ''} onChange={e => form.setData('nombre_echeances', e.target.value)} required />
                                        </div>

                                        <div className="col-md-3">
                                            <label htmlFor="date_debut" className="form-label fw-semibold">Date de début</label>
                                            <input id="date_debut" type="date" className="form-control" value={form.data.date_debut || ''} onChange={e => form.setData('date_debut', e.target.value)} required />
                                        </div>

                                        {/* DIFFÉRÉ */}
                                        <div className="col-md-3">
                                            <label htmlFor="differe" className="form-label fw-semibold">Différé (Échéances)</label>
                                            <input id="differe" type="number" min="0" className="form-control" placeholder="0" value={form.data.differe || 0} onChange={e => form.setData('differe', e.target.value)} />
                                        </div>

                                        {/* 6. FRAIS DE DOSSIER & GARANTIE */}
                                        <div className="col-md-6">
                                            <label htmlFor="frais_dossier" className="form-label fw-semibold text-success">Frais de dossier (FCFA)</label>
                                            <input 
                                                id="frais_dossier" 
                                                type="number" 
                                                min="0" 
                                                className="form-control border-success bg-success bg-opacity-10" 
                                                placeholder="Frais d'instruction"
                                                value={form.data.frais_dossier || ''} 
                                                onChange={e => form.setData('frais_dossier', e.target.value)} 
                                            />
                                        </div>

                                        <div className="col-md-6">
                                            <label htmlFor="nantissement" className="form-label fw-semibold text-info">Garantie / Nantissement requis (FCFA)</label>
                                            <input 
                                                id="nantissement" 
                                                type="number" 
                                                min="0" 
                                                className="form-control border-info bg-info bg-opacity-10" 
                                                placeholder="Épargne bloquée obligatoirement"
                                                value={form.data.nantissement || ''} 
                                                onChange={e => form.setData('nantissement', e.target.value)} 
                                            />
                                        </div>

                                        {/* 7. TARIFICATION */}
                                        <div className="col-md-4">
                                            <label htmlFor="mode_calcul" className="form-label fw-semibold">Mode de calcul</label>
                                            <select id="mode_calcul" className="form-select" value={form.data.mode || 'fixe'} onChange={e => form.setData('mode', e.target.value)}>
                                                <option value="fixe">Fixe (Flat)</option>
                                                <option value="degressif">Dégressif (Amortissement réel)</option>
                                            </select>
                                        </div>

                                        <div className="col-md-4">
                                            <label htmlFor="taux" className="form-label fw-semibold">Taux standard (%)</label>
                                            <input id="taux" type="number" step="0.01" className="form-control text-muted bg-light" value={form.data.taux || ''} readOnly />
                                        </div>

                                        <div className="col-md-4">
                                            <label htmlFor="taux_manuelle" className="form-label text-warning fw-semibold">Taux manuel (%)</label>
                                            <input id="taux_manuelle" type="number" step="0.01" className="form-control border-warning fw-bold text-warning" value={form.data.taux_manuelle || ''} onChange={e => form.setData('taux_manuelle', e.target.value)} placeholder="Dérogation gérant" />
                                        </div>
                                    </div>
                                </fieldset>

                                {/* NAVIGATION */}
                                <div className="d-flex justify-content-between pt-3 border-top mt-4">
                                    <button type="button" className="btn btn-outline-secondary px-4" onClick={() => handleTabChange('details')}>
                                        <i className="bi bi-arrow-left me-2"></i> Retour aux détails
                                    </button>
                                    <button type="button" className="btn btn-primary px-4 shadow-sm" onClick={() => handleTabChange('resumes')}>
                                        Étape 4 : Échéancier <i className="bi bi-arrow-right ms-2"></i>
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
                                    <button type="button" className="btn btn-outline-secondary px-4" onClick={() => handleTabChange('simulation')}>
                                        <i className="bi bi-arrow-left me-1"></i> Retour à la simulation
                                    </button>
                                    <button type="button" className="btn btn-primary px-4" onClick={() => handleTabChange('garanties')}>
                                        Étape 5 : Garanties & Pièces Justificatives <i className="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        )}
                        {/* =========================================
                            ONGLET 5 : GARANTIES & PIÈCES JUSTIFICATIVES
                        ========================================= */}
                        {activeTab === 'garanties' && (
                            <div className="animate__animated animate__fadeIn">
                                <div className="row">
                                    {/* COMPOSANT GAUCHE : FORMULAIRE DU GARANT / AVALISTE */}
                                    <div className="col-lg-7">
                                        <div className="card border-0 shadow-sm mb-4">
                                            <div className="card-body p-4">
                                                <fieldset>
                                                    <legend className="text-uppercase h6 text-warning border-bottom pb-2 mb-3 fw-bold">
                                                        <i className="bi bi-shield-check me-2"></i>4.1 Caution Solidaire / Avaliste
                                                    </legend>
                                                    <p className="text-muted small mb-4">
                                                        Renseignez les informations de la personne physique qui se porte garante du remboursement en cas de défaillance du bénéficiaire.
                                                    </p>

                                                    <div className="row g-3">
                                                        <div className="col-md-12">
                                                            <label htmlFor="garant_nom_prenom" className="form-label fw-semibold">Nom &amp; Prénoms du garant <span className="text-danger">*</span></label>
                                                            <div className="input-group">
                                                                <span className="input-group-text bg-light"><i className="bi bi-person"></i></span>
                                                                <input 
                                                                    id="garant_nom_prenom" 
                                                                    type="text" 
                                                                    className={`form-control ${form.errors.garant_nom_prenom ? 'is-invalid' : ''}`} 
                                                                    placeholder="Ex: Jean KOFFI"
                                                                    value={form.data.garant_nom_prenom} 
                                                                    onChange={e => form.setData('garant_nom_prenom', e.target.value)} 
                                                                    required
                                                                />
                                                            </div>
                                                            <ErrorMsg field="garant_nom_prenom" />
                                                        </div>

                                                        <div className="col-md-12">
                                                            <label htmlFor="garant_telephone" className="form-label fw-semibold">Numéro de Téléphone <span className="text-danger">*</span></label>
                                                            <div className="input-group">
                                                                <span className="input-group-text bg-light"><i className="bi bi-telephone"></i></span>
                                                                <input 
                                                                    id="garant_telephone" 
                                                                    type="tel" 
                                                                    className={`form-control ${form.errors.garant_telephone ? 'is-invalid' : ''}`} 
                                                                    placeholder="Ex: +228 90 00 00 00"
                                                                    value={form.data.garant_telephone} 
                                                                    onChange={e => form.setData('garant_telephone', e.target.value)} 
                                                                    required
                                                                />
                                                            </div>
                                                            <ErrorMsg field="garant_telephone" />
                                                        </div>

                                                        <div className="col-md-6">
                                                            <label htmlFor="garant_profession" className="form-label fw-semibold">Profession / Secteur d'activité</label>
                                                            <input 
                                                                id="garant_profession" 
                                                                type="text" 
                                                                className="form-control" 
                                                                placeholder="Ex: Revendeuse, Fonctionnaire..."
                                                                value={form.data.garant_profession} 
                                                                onChange={e => form.setData('garant_profession', e.target.value)} 
                                                            />
                                                            <ErrorMsg field="garant_profession" />
                                                        </div>

                                                        <div className="col-md-6">
                                                            <label htmlFor="garant_adresse" className="form-label fw-semibold">Quartier de résidence</label>
                                                            <input 
                                                                id="garant_adresse" 
                                                                type="text" 
                                                                className="form-control" 
                                                                placeholder="Ex: Adidogomé, Hedzranawoé"
                                                                value={form.data.garant_adresse} 
                                                                onChange={e => form.setData('garant_adresse', e.target.value)} 
                                                            />
                                                            <ErrorMsg field="garant_adresse" />
                                                        </div>
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                    </div>

                                    {/* COMPOSANT DROIT : PIÈCES JUSTIFICATIVES NUMÉRIQUES (KYC) */}
                                    <div className="col-lg-5">
                                        <div className="card border-0 shadow-sm mb-4">
                                            <div className="card-body p-4">
                                                <fieldset>
                                                    <legend className="text-uppercase h6 text-info border-bottom pb-2 mb-3 fw-bold">
                                                        <i className="bi bi-file-earmark-arrow-up me-2"></i>4.2 Documents &amp; KYC d'Audit
                                                    </legend>
                                                    <p className="text-muted small mb-3">
                                                        Joignez les scans ou photos lisibles pour la conformité réglementaire de la microfinance.
                                                    </p>

                                                    <div className="mb-4">
                                                        <label htmlFor="piece_identite" className="form-label fw-semibold">
                                                            Pièce d'identité (Client) <span className="text-danger">*</span>
                                                        </label>
                                                        <div className="p-3 border border-dashed rounded bg-light text-center position-relative">
                                                            <i className="bi bi-card-image text-muted h3 d-block mb-2"></i>
                                                            <input 
                                                                id="piece_identite" 
                                                                type="file" 
                                                                className={`form-control form-control-sm ${form.errors.piece_identite ? 'is-invalid' : ''}`} 
                                                                accept="image/*,application/pdf"
                                                                onChange={e => form.setData('piece_identite', e.target.files[0])} 
                                                                required
                                                            />
                                                            <div className="form-text small text-muted mt-1">CNIB, Passeport ou Carte d'Électeur (Max 5Mo).</div>
                                                            {form.data.piece_identite && (
                                                                <div className="badge bg-success mt-2">
                                                                    <i className="bi bi-check-circle-fill me-1"></i> Fichier sélectionné
                                                                </div>
                                                            )}
                                                        </div>
                                                        <ErrorMsg field="piece_identite" />
                                                    </div>

                                                    <div className="mb-2">
                                                        <label htmlFor="justificatif_revenu" className="form-label fw-semibold">
                                                            Preuve d'activité ou Localisation
                                                        </label>
                                                        <div className="p-3 border border-dashed rounded bg-light text-center position-relative">
                                                            <i className="bi bi-file-earmark-pdf text-muted h3 d-block mb-2"></i>
                                                            <input 
                                                                id="justificatif_revenu" 
                                                                type="file" 
                                                                className={`form-control form-control-sm ${form.errors.justificatif_revenu ? 'is-invalid' : ''}`} 
                                                                accept="image/*,application/pdf"
                                                                onChange={e => form.setData('justificatif_revenu', e.target.files[0])} 
                                                            />
                                                            <div className="form-text small text-muted mt-1">Facture CEET/TdE, Plan ou Carte CFE / Registre.</div>
                                                            {form.data.justificatif_revenu && (
                                                                <div className="badge bg-success mt-2">
                                                                    <i className="bi bi-check-circle-fill me-1"></i> Fichier sélectionné
                                                                </div>
                                                            )}
                                                        </div>
                                                        <ErrorMsg field="justificatif_revenu" />
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="text-muted small d-none d-lg-block flex-grow-1 text-center px-2">
                                    <i className="bi bi-info-circle text-primary me-1"></i> Tous les champs marqués d'une astérisque (<span className="text-danger">*</span>) sont requis pour le comité.
                                </div>                             
                                {/* BOUTONS DE NAVIGATION BASSE */}
                                <div className="card border-0 shadow-sm mt-4">
                                    <div className="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        
                                        {/* BOUTON RETOUR : Plus court pour éviter le retour à la ligne automatique */}
                                        <button 
                                            type="button" 
                                            className="btn btn-outline-secondary px-4 text-nowrap" 
                                            onClick={() => handleTabChange('resumes')}
                                        >
                                            <i className="bi bi-arrow-left me-2"></i>Retour aux résumés
                                        </button>
                                        
                                        {/* TEXTE CENTRAL : Un petit conteneur avec flex-shrink pour lui laisser de la place sans pousser les boutons */}

                                        
                                        {/* BOUTON SOUMISSION : Reste à droite, bien proportionné */}
                                        <button 
                                            type="submit" 
                                            className="btn btn-success px-5 shadow-sm text-nowrap fw-semibold" 
                                            disabled={form.processing}
                                        >
                                            {form.processing ? (
                                                <>
                                                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                    Chargement...
                                                </>
                                            ) : (
                                                'Enregistrer la demande'
                                            )}
                                        </button>

                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}