import { Link, useForm } from '@inertiajs/inertia-react';
import { useEffect, useMemo, useState } from 'react';
import AdminLayout from '../../Layouts/AdminLayout.jsx';
import { buildScheduleFromForm, formatCurrency, formatDateToFR } from '../../Utils/creditHelpers';

export default function Create({ clients }) {
    const form = useForm({
        client_id: '',
        carnet_id: '',
        montant_demande: 0,
        type: 'compte',
        mode: 'degressif',
        periodicite: 'mensuelle',
        nombre_echeances: 3,
        taux: 1.5,
        taux_manuelle: '',
        date_debut: new Date().toISOString().slice(0, 10),
    });

    const [carnets, setCarnets] = useState([]);
    const today = new Date().toISOString().slice(0, 10);
    const isCompteCredit = form.data.type === 'compte';
    const selectedCarnet = carnets.find(carnet => String(carnet.id) === String(form.data.carnet_id));
    const isCompteCarnetSelected = selectedCarnet?.type === 'compte';
    const isTontineCarnetSelected = selectedCarnet?.type === 'tontine';
    const isTypeFixedByCarnet = !!selectedCarnet;
    const pointageWarning = isTontineCarnetSelected && selectedCarnet?.total_pointages < selectedCarnet?.required_pointages;

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
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Carnets fetched:', data);
                if (Array.isArray(data)) {
                    setCarnets(data);
                    if (!data.some(item => String(item.id) === String(form.data.carnet_id))) {
                        form.setData('carnet_id', '');
                    }
                } else {
                    console.error('Expected array, got:', data);
                    setCarnets([]);
                }
            })
            .catch(err => {
                console.error('Error fetching carnets:', err);
                setCarnets([]);
            });

        return () => controller.abort();
    }, [form.data.client_id]);

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

    const totalInterest = useMemo(
        () => schedule.reduce((sum, row) => sum + row.interest, 0),
        [schedule],
    );

    const totalDue = useMemo(
        () => schedule.reduce((sum, row) => sum + row.total, 0),
        [schedule],
    );

    const meanInstallment = useMemo(
        () => (schedule.length ? totalDue / schedule.length : 0),
        [schedule, totalDue],
    );

    const submit = e => {
        e.preventDefault();
        form.post('/admin/credits', {
            onSuccess: () => {
                form.reset(
                    'montant_demande',
                    'type',
                    'mode',
                    'periodicite',
                    'nombre_echeances',
                    'taux',
                    'taux_manuelle',
                    'date_debut',
                );
            },
        });
    };

    return (
        <AdminLayout>
            <div>
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 className="h3">Nouvelle demande de crédit</h1>
                        <p className="text-muted mb-0">
                            Saisie interactive et prévisualisation des échéances.
                        </p>
                    </div>
                    <Link href="/admin/credits" className="btn btn-outline-secondary">
                        Retour
                    </Link>
                </div>

                <form onSubmit={submit} className="card shadow-sm p-4">
                    <div className="row gy-3">
                        <div className="col-md-6">
                            <span>Nombre de carnets : {carnets.length}</span>
                            <label className="form-label">Client</label>
                            <select
                                className="form-select"
                                value={form.data.client_id}
                                onChange={e => form.setData('client_id', e.target.value)}
                                required
                            >
                                <option value="">Sélectionner un client</option>
                                {clients.map(client => (
                                    <option key={client.id} value={client.id}>
                                        {client.nom} {client.prenom}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-md-6">
                            <label className="form-label">
                                Carnet
                                {isCompteCredit && (
                                    <span className="text-danger ms-2">*</span>
                                )}
                            </label>
                            <select
                                className={`form-select ${form.errors.carnet_id ? 'is-invalid' : ''}`}
                                value={form.data.carnet_id}
                                onChange={e => form.setData('carnet_id', e.target.value)}
                            >
                                <option value="">
                                    {isCompteCredit ? 'Sélectionner un carnet obligatoire' : 'Sélectionner un carnet (optionnel)'}
                                </option>
                                {carnets.length === 0 && form.data.client_id && (
                                    <option value="">Aucun carnet actif trouvé</option>
                                )}
                                {carnets.map(carnet => (
                                    <option key={carnet.id} value={carnet.id}>
                                        {carnet.type === 'tontine' ? 'Tontine' : 'Compte'} {carnet.numero}
                                    </option>
                                ))}
                            </select>
                            {form.errors.carnet_id && (
                                <div className="invalid-feedback">{form.errors.carnet_id}</div>
                            )}
                            {!form.errors.carnet_id && isCompteCredit && carnets.length === 0 && form.data.client_id && (
                                <div className="form-text text-warning">
                                    Le client ne possède pas de carnet de compte actif. Veuillez d'abord créer un carnet.
                                </div>
                            )}
                            {selectedCarnet && (
                                <div className="form-text text-muted mt-2">
                                    {selectedCarnet.type === 'tontine' ? (
                                        <>Catégorie : {selectedCarnet.category || 'N/A'} • Cycles : {selectedCarnet.nombre_cycles || 'N/A'} • Pointages : {selectedCarnet.total_pointages ?? 0}/{selectedCarnet.required_pointages ?? '?'}</>
                                    ) : (
                                        <>Compte lié : {selectedCarnet.linked_tontine ? selectedCarnet.linked_tontine.numero : 'Aucun'}</>
                                    )}
                                </div>
                            )}
                            {selectedCarnet && (
                                <div className="form-text text-muted mt-1">
                                    Assiette de l'épargne : {formatCurrency(selectedCarnet.available_savings ?? 0)} • Garantie maximale possible : {formatCurrency(selectedCarnet.guarantee_base ?? 0)}
                                </div>
                            )}
                            {pointageWarning && (
                                <div className="form-text text-warning">
                                    Seuil recommandé non atteint, mais l’admin peut enregistrer le crédit malgré tout.
                                </div>
                            )}
                        </div>

                        <div className="col-md-6">
                            <label className="form-label">Montant demandé</label>
                            <input
                                type="number"
                                className={`form-control ${form.errors.montant_demande ? 'is-invalid' : ''}`}
                                value={form.data.montant_demande}
                                onChange={e => form.setData('montant_demande', e.target.value)}
                                min="1000"
                                required
                            />
                            {form.errors.montant_demande && (
                                <div className="invalid-feedback">{form.errors.montant_demande}</div>
                            )}
                        </div>

                        <div className="col-md-4">
                            <label className="form-label">Type de crédit</label>
                            <select
                                className="form-select"
                                value={form.data.type}
                                onChange={e => form.setData('type', e.target.value)}
                                required
                                disabled={isTypeFixedByCarnet}
                            >
                                {selectedCarnet?.type === 'compte' ? (
                                    <>
                                        <option value="compte">Crédit sur compte</option>
                                    </>
                                ) : selectedCarnet?.type === 'tontine' ? (
                                    <>
                                        <option value="quinzaine">Crédit quinzaine</option>
                                    </>
                                ) : (
                                    <>
                                        <option value="">Choisir</option>
                                        <option value="compte">Crédit sur compte</option>
                                        <option value="quinzaine">Crédit quinzaine</option>
                                        <option value="mensuel">Crédit mensuel</option>
                                    </>
                                )}
                            </select>
                            {selectedCarnet?.type === 'compte' && (
                                <div className="form-text text-muted">
                                    Le type est fixé à Crédit sur compte pour ce carnet.
                                </div>
                            )}
                            {selectedCarnet?.type === 'tontine' && (
                                <div className="form-text text-muted">
                                    Le type est fixé à Crédit quinzaine pour ce carnet de tontine.
                                </div>
                            )}
                        </div>

                        <div className="col-md-4">
                            <label className="form-label">Mode de calcul</label>
                            <select
                                className="form-select"
                                value={form.data.mode}
                                onChange={e => form.setData('mode', e.target.value)}
                                required
                            >
                                <option value="">Choisir</option>
                                <option value="fixe">Fixe</option>
                                <option value="degressif">Dégressif</option>
                            </select>
                        </div>

                        <div className="col-md-4">
                            <label className="form-label">Périodicité</label>
                            <select
                                className="form-select"
                                value={form.data.periodicite}
                                onChange={e => form.setData('periodicite', e.target.value)}
                                required
                            >
                                <option value="">Choisir</option>
                                <option value="quinzaine">Quinzaine</option>
                                <option value="mensuelle">Mensuelle</option>
                            </select>
                        </div>

                        <div className="col-md-4">
                            <label className="form-label">Nombre d'échéances</label>
                            <input
                                type="number"
                                className="form-control"
                                value={form.data.nombre_echeances}
                                onChange={e => form.setData('nombre_echeances', e.target.value)}
                                min="1"
                                required
                            />
                        </div>

                        <div className="col-md-4">
                            <label className="form-label">Taux standard (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="form-control"
                                value={form.data.taux}
                                onChange={e => form.setData('taux', e.target.value)}
                                required
                            />
                        </div>

                        <div className="col-md-4">
                            <label className="form-label">Taux manuel (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="form-control"
                                value={form.data.taux_manuelle}
                                onChange={e => form.setData('taux_manuelle', e.target.value)}
                                placeholder="Optionnel"
                            />
                        </div>

                        <div className="col-md-12">
                            <label className="form-label">Date de début</label>
                            <input
                                type="date"
                                className={`form-control ${form.errors.date_debut ? 'is-invalid' : ''}`}
                                value={form.data.date_debut}
                                onChange={e => form.setData('date_debut', e.target.value)}
                                min={today}
                                required
                            />
                            {form.errors.date_debut && (
                                <div className="invalid-feedback">{form.errors.date_debut}</div>
                            )}
                        </div>

                        <div className="col-12">
                            <div className="row gy-3">
                                <div className="col-md-4">
                                    <div className="border rounded-3 p-3 bg-light">
                                        <div className="text-muted">Montant total</div>
                                        <div className="fs-4 fw-bold">
                                            {formatCurrency(totalDue)}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-4">
                                    <div className="border rounded-3 p-3 bg-light">
                                        <div className="text-muted">Intérêt total</div>
                                        <div className="fs-4 fw-bold">
                                            {formatCurrency(totalInterest)}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-4">
                                    <div className="border rounded-3 p-3 bg-light">
                                        <div className="text-muted">Échéance moyenne</div>
                                        <div className="fs-4 fw-bold">
                                            {formatCurrency(meanInstallment)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="col-12">
                            <div className="card border-secondary">
                                <div className="card-header bg-white">
                                    <strong>Aperçu des échéances</strong>
                                </div>
                                <div className="card-body p-0">
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
                                            {paginatedSchedule.map(item => (
                                                <tr key={item.numero}>
                                                    <td>{item.numero}</td>
                                                    <td>{formatDateToFR(item.date)}</td>
                                                    <td>{formatCurrency(item.principal)}</td>
                                                    <td>{formatCurrency(item.interest)}</td>
                                                    <td>{formatCurrency(item.total)}</td>
                                                </tr>
                                            ))}
                                            {schedule.length === 0 && (
                                                <tr>
                                                    <td colSpan="5" className="text-center py-4">
                                                        Remplissez le formulaire pour afficher le plan.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                                {schedule.length > pageSize && (
                                    <div className="card-footer bg-white border-top">
                                        <nav>
                                            <ul className="pagination justify-content-center mb-0">
                                                <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                                                    <button
                                                        type="button"
                                                        className="page-link"
                                                        onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                                                    >
                                                        Précédent
                                                    </button>
                                                </li>
                                                {Array.from({ length: pageCount }, (_, index) => (
                                                    <li
                                                        key={index}
                                                        className={`page-item ${currentPage === index + 1 ? 'active' : ''}`}
                                                    >
                                                        <button
                                                            type="button"
                                                            className="page-link"
                                                            onClick={() => setCurrentPage(index + 1)}
                                                        >
                                                            {index + 1}
                                                        </button>
                                                    </li>
                                                ))}
                                                <li className={`page-item ${currentPage === pageCount ? 'disabled' : ''}`}>
                                                    <button
                                                        type="button"
                                                        className="page-link"
                                                        onClick={() => setCurrentPage(prev => Math.min(prev + 1, pageCount))}
                                                    >
                                                        Suivant
                                                    </button>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="col-12 text-end">
                            <button type="submit" className="btn btn-primary">
                                Enregistrer la demande
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
