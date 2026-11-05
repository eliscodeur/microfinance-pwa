import { useForm } from "@inertiajs/inertia-react";
import { useState } from "react";
import AdminLayout from "../../Layouts/AdminLayout.jsx";

const TABS = [
    { key: "products", label: "Produits", icon: "bi-box-seam" },
    { key: "types", label: "Types", icon: "bi-diagram-3" },
    { key: "objects", label: "Motifs", icon: "bi-bullseye" },
];

const activeForm = (form) =>
    form.setData("is_active", !form.data.is_active);

function FieldError({ form, name }) {
    return form.errors[name] ? (
        <div className="text-danger small mt-1">{form.errors[name]}</div>
    ) : null;
}

export default function Settings({ types = [], products = [], objects = [] }) {
    const [tab, setTab] = useState("products");
    const [editing, setEditing] = useState(null);
    const typeForm = useForm({ nom: "", code: "", is_active: true });
    const objectForm = useForm({
        nom: "",
        secteur_activite: "",
        is_active: true,
    });
    const productForm = useForm({
        credit_type_id: "",
        nom: "",
        code: "",
        type_carnet_requis: "compte",
        taux_interet_defaut: "",
        frais_dossier_defaut: "",
        duree_max_mois: 1,
        is_active: true,
        object_ids: [],
    });

    const clearForm = (kind) => {
        setEditing(null);
        if (kind === "types") typeForm.reset();
        if (kind === "objects") objectForm.reset();
        if (kind === "products") productForm.reset();
    };

    const beginEdit = (kind, item) => {
        setEditing({ kind, id: item.id });
        if (kind === "types") {
            typeForm.setData({
                nom: item.nom,
                code: item.code,
                is_active: Boolean(item.is_active),
            });
        } else if (kind === "objects") {
            objectForm.setData({
                nom: item.nom,
                secteur_activite: item.secteur_activite || "",
                is_active: Boolean(item.is_active),
            });
        } else {
            productForm.setData({
                credit_type_id: String(item.credit_type_id),
                nom: item.nom,
                code: item.code,
                type_carnet_requis: item.type_carnet_requis,
                taux_interet_defaut: item.taux_interet_defaut,
                frais_dossier_defaut: item.frais_dossier_defaut,
                duree_max_mois: item.duree_max_mois,
                is_active: Boolean(item.is_active),
                object_ids: (item.credit_objects || []).map((object) =>
                    String(object.id),
                ),
            });
        }
    };

    const submit = (kind, form) => (event) => {
        event.preventDefault();
        const isEditing = editing?.kind === kind;
        const url = isEditing
            ? `/admin/credit-settings/${kind}/${editing.id}`
            : `/admin/credit-settings/${kind}`;
        const options = {
            preserveScroll: true,
            onSuccess: () => clearForm(kind),
        };
        if (isEditing) form.put(url, options);
        else form.post(url, options);
    };

    const toggleObject = (id) => {
        const selected = productForm.data.object_ids;
        productForm.setData(
            "object_ids",
            selected.includes(String(id))
                ? selected.filter((item) => item !== String(id))
                : [...selected, String(id)],
        );
    };

    const renderStatus = (item) => (
        <span
            className={`badge ${item.is_active ? "bg-success" : "bg-secondary"}`}
        >
            {item.is_active ? "Actif" : "Inactif"}
        </span>
    );

    return (
        <AdminLayout>
            <main className="container-fluid py-4">
                <div className="mb-4">
                    <h1 className="h3 fw-bold mb-1">Paramétrage des crédits</h1>
                    <p className="text-muted mb-0">
                        Gérez les produits, types et motifs proposés lors d’une
                        demande de crédit.
                    </p>
                </div>

                <ul className="nav nav-tabs mb-4">
                    {TABS.map((item) => (
                        <li className="nav-item" key={item.key}>
                            <button
                                type="button"
                                className={`nav-link ${tab === item.key ? "active" : ""}`}
                                onClick={() => {
                                    setTab(item.key);
                                    setEditing(null);
                                }}
                            >
                                <i className={`bi ${item.icon} me-2`} />
                                {item.label}
                            </button>
                        </li>
                    ))}
                </ul>

                {tab === "types" && (
                    <div className="row g-4">
                        <div className="col-lg-4">
                            <form
                                className="card border-0 shadow-sm"
                                onSubmit={submit("types", typeForm)}
                            >
                                <div className="card-body">
                                    <h2 className="h5 mb-3">
                                        {editing?.kind === "types"
                                            ? "Modifier le type"
                                            : "Ajouter un type"}
                                    </h2>
                                    <label className="form-label">Nom</label>
                                    <input
                                        className="form-control mb-1"
                                        value={typeForm.data.nom}
                                        onChange={(e) =>
                                            typeForm.setData("nom", e.target.value)
                                        }
                                        required
                                    />
                                    <FieldError form={typeForm} name="nom" />
                                    <label className="form-label mt-3">Code</label>
                                    <input
                                        className="form-control mb-1"
                                        value={typeForm.data.code}
                                        onChange={(e) =>
                                            typeForm.setData("code", e.target.value)
                                        }
                                        required
                                    />
                                    <FieldError form={typeForm} name="code" />
                                    <div className="form-check mt-3">
                                        <input
                                            id="type-active"
                                            className="form-check-input"
                                            type="checkbox"
                                            checked={typeForm.data.is_active}
                                            onChange={() => activeForm(typeForm)}
                                        />
                                        <label className="form-check-label" htmlFor="type-active">
                                            Type actif
                                        </label>
                                    </div>
                                </div>
                                <div className="card-footer bg-white d-flex gap-2">
                                    <button
                                        className="btn btn-primary"
                                        disabled={typeForm.processing}
                                    >
                                        {editing?.kind === "types" ? "Enregistrer" : "Ajouter"}
                                    </button>
                                    {editing?.kind === "types" && (
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary"
                                            onClick={() => clearForm("types")}
                                        >
                                            Annuler
                                        </button>
                                    )}
                                </div>
                            </form>
                        </div>
                        <div className="col-lg-8">
                            <div className="card border-0 shadow-sm">
                                <div className="table-responsive">
                                    <table className="table align-middle mb-0">
                                        <thead className="table-light">
                                            <tr>
                                                <th>Nom</th><th>Code</th><th>Produits</th><th>État</th><th />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {types.map((item) => (
                                                <tr key={item.id}>
                                                    <td>{item.nom}</td>
                                                    <td>{item.code}</td>
                                                    <td>{item.products_count}</td>
                                                    <td>{renderStatus(item)}</td>
                                                    <td className="text-end">
                                                        <button className="btn btn-sm btn-outline-primary" onClick={() => beginEdit("types", item)}>Modifier</button>
                                                    </td>
                                                </tr>
                                            ))}
                                            {!types.length && <tr><td colSpan="5" className="text-center text-muted py-4">Aucun type configuré.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {tab === "objects" && (
                    <div className="row g-4">
                        <div className="col-lg-4">
                            <form
                                className="card border-0 shadow-sm"
                                onSubmit={submit("objects", objectForm)}
                            >
                                <div className="card-body">
                                    <h2 className="h5 mb-3">
                                        {editing?.kind === "objects"
                                            ? "Modifier le motif"
                                            : "Ajouter un motif"}
                                    </h2>
                                    <label className="form-label">Nom</label>
                                    <input className="form-control" value={objectForm.data.nom} onChange={(e) => objectForm.setData("nom", e.target.value)} required />
                                    <FieldError form={objectForm} name="nom" />
                                    <label className="form-label mt-3">Secteur d’activité</label>
                                    <input className="form-control" value={objectForm.data.secteur_activite} onChange={(e) => objectForm.setData("secteur_activite", e.target.value)} />
                                    <FieldError form={objectForm} name="secteur_activite" />
                                    <div className="form-check mt-3">
                                        <input id="object-active" className="form-check-input" type="checkbox" checked={objectForm.data.is_active} onChange={() => activeForm(objectForm)} />
                                        <label className="form-check-label" htmlFor="object-active">Motif actif</label>
                                    </div>
                                </div>
                                <div className="card-footer bg-white d-flex gap-2">
                                    <button className="btn btn-primary" disabled={objectForm.processing}>{editing?.kind === "objects" ? "Enregistrer" : "Ajouter"}</button>
                                    {editing?.kind === "objects" && <button type="button" className="btn btn-outline-secondary" onClick={() => clearForm("objects")}>Annuler</button>}
                                </div>
                            </form>
                        </div>
                        <div className="col-lg-8">
                            <div className="card border-0 shadow-sm">
                                <div className="table-responsive">
                                    <table className="table align-middle mb-0">
                                        <thead className="table-light"><tr><th>Motif</th><th>Secteur</th><th>Produits</th><th>État</th><th /></tr></thead>
                                        <tbody>
                                            {objects.map((item) => (
                                                <tr key={item.id}>
                                                    <td>{item.nom}</td><td>{item.secteur_activite || "—"}</td><td>{item.credit_products_count}</td><td>{renderStatus(item)}</td>
                                                    <td className="text-end"><button className="btn btn-sm btn-outline-primary" onClick={() => beginEdit("objects", item)}>Modifier</button></td>
                                                </tr>
                                            ))}
                                            {!objects.length && <tr><td colSpan="5" className="text-center text-muted py-4">Aucun motif configuré.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {tab === "products" && (
                    <div className="row g-4">
                        <div className="col-lg-5">
                            <form
                                className="card border-0 shadow-sm"
                                onSubmit={submit("products", productForm)}
                            >
                                <div className="card-body">
                                    <h2 className="h5 mb-3">
                                        {editing?.kind === "products"
                                            ? "Modifier le produit"
                                            : "Ajouter un produit"}
                                    </h2>
                                    <div className="row g-3">
                                        <div className="col-md-8">
                                            <label className="form-label">Nom</label>
                                            <input className="form-control" value={productForm.data.nom} onChange={(e) => productForm.setData("nom", e.target.value)} required />
                                            <FieldError form={productForm} name="nom" />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label">Code</label>
                                            <input className="form-control" value={productForm.data.code} onChange={(e) => productForm.setData("code", e.target.value)} required />
                                            <FieldError form={productForm} name="code" />
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Type de crédit</label>
                                            <select className="form-select" value={productForm.data.credit_type_id} onChange={(e) => productForm.setData("credit_type_id", e.target.value)} required>
                                                <option value="">Choisir un type</option>
                                                {types.map((item) => (
                                                    <option
                                                        key={item.id}
                                                        value={item.id}
                                                        disabled={!item.is_active && String(item.id) !== String(productForm.data.credit_type_id)}
                                                    >
                                                        {item.nom}{item.is_active ? "" : " (inactif)"}
                                                    </option>
                                                ))}
                                            </select>
                                            <FieldError form={productForm} name="credit_type_id" />
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label">Carnet requis</label>
                                            <select className="form-select" value={productForm.data.type_carnet_requis} onChange={(e) => productForm.setData("type_carnet_requis", e.target.value)}>
                                                <option value="compte">Compte</option><option value="tontine">Tontine</option>
                                            </select>
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label">Taux (%)</label>
                                            <input className="form-control" type="number" step="0.01" min="0" max="100" value={productForm.data.taux_interet_defaut} onChange={(e) => productForm.setData("taux_interet_defaut", e.target.value)} required />
                                            <FieldError form={productForm} name="taux_interet_defaut" />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label">Frais de dossier</label>
                                            <input className="form-control" type="number" step="1" min="0" value={productForm.data.frais_dossier_defaut} onChange={(e) => productForm.setData("frais_dossier_defaut", e.target.value)} required />
                                            <FieldError form={productForm} name="frais_dossier_defaut" />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label">Durée max. (mois)</label>
                                            <input className="form-control" type="number" min="1" max="120" value={productForm.data.duree_max_mois} onChange={(e) => productForm.setData("duree_max_mois", e.target.value)} required />
                                            <FieldError form={productForm} name="duree_max_mois" />
                                        </div>
                                        <div className="col-12">
                                            <label className="form-label">Motifs autorisés</label>
                                            <div className="border rounded p-3" style={{ maxHeight: 180, overflowY: "auto" }}>
                                                {objects.map((item) => (
                                                    <div className="form-check" key={item.id}>
                                                        <input
                                                            id={`object-${item.id}`}
                                                            className="form-check-input"
                                                            type="checkbox"
                                                            checked={productForm.data.object_ids.includes(String(item.id))}
                                                            disabled={!item.is_active && !productForm.data.object_ids.includes(String(item.id))}
                                                            onChange={() => toggleObject(item.id)}
                                                        />
                                                        <label className="form-check-label" htmlFor={`object-${item.id}`}>{item.nom}{item.is_active ? "" : " (inactif)"}</label>
                                                    </div>
                                                ))}
                                                {!objects.length && <span className="text-muted small">Créez d’abord un motif.</span>}
                                            </div>
                                            <FieldError form={productForm} name="object_ids" />
                                        </div>
                                        <div className="col-12">
                                            <div className="form-check">
                                                <input id="product-active" className="form-check-input" type="checkbox" checked={productForm.data.is_active} onChange={() => activeForm(productForm)} />
                                                <label className="form-check-label" htmlFor="product-active">Produit actif</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="card-footer bg-white d-flex gap-2">
                                    <button className="btn btn-primary" disabled={productForm.processing || !types.length}>{editing?.kind === "products" ? "Enregistrer" : "Ajouter"}</button>
                                    {editing?.kind === "products" && <button type="button" className="btn btn-outline-secondary" onClick={() => clearForm("products")}>Annuler</button>}
                                </div>
                            </form>
                        </div>
                        <div className="col-lg-7">
                            <div className="card border-0 shadow-sm">
                                <div className="table-responsive">
                                    <table className="table align-middle mb-0">
                                        <thead className="table-light"><tr><th>Produit</th><th>Type</th><th>Support</th><th>Taux</th><th>État</th><th /></tr></thead>
                                        <tbody>
                                            {products.map((item) => (
                                                <tr key={item.id}>
                                                    <td><span className="fw-semibold">{item.nom}</span><div className="small text-muted">{item.code}</div></td>
                                                    <td>{item.type?.nom || "—"}</td><td>{item.type_carnet_requis}</td><td>{item.taux_interet_defaut}%</td><td>{renderStatus(item)}</td>
                                                    <td className="text-end"><button className="btn btn-sm btn-outline-primary" onClick={() => beginEdit("products", item)}>Modifier</button></td>
                                                </tr>
                                            ))}
                                            {!products.length && <tr><td colSpan="6" className="text-center text-muted py-4">Aucun produit configuré.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </main>
        </AdminLayout>
    );
}
