@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Modifier l'Agent : {{ $employe->nom }} {{ $employe->prenoms }}</h1>
                <p class="text-muted small mb-0">Mise à jour des informations administratives et contractuelles.</p>
            </div>
            <a href="{{ route('admin.employes.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Retour à la liste
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form action="{{ route('admin.employes.update', $employe->ulid) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h5 class="text-primary fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>1. État Civil & Identité
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom"
                                value="{{ old('nom', $employe->nom) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Prénoms <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prenoms"
                                value="{{ old('prenoms', $employe->prenoms) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sexe</label>
                            <select name="sexe" class="form-select">
                                <option value="">Sélectionner...</option>
                                <option value="M" {{ old('sexe', $employe->sexe) == 'M' ? 'selected' : '' }}>Masculin
                                </option>
                                <option value="F" {{ old('sexe', $employe->sexe) == 'F' ? 'selected' : '' }}>Féminin
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date de naissance</label>
                            <input type="date" class="form-control" name="date_naissance"
                                value="{{ old('date_naissance', optional($employe->date_naissance)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Lieu de naissance</label>
                            <input type="text" class="form-control" name="lieu_naissance"
                                value="{{ old('lieu_naissance', $employe->lieu_naissance) }}">
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <h5 class="text-primary fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>2. Coordonnées</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="telephone"
                                value="{{ old('telephone', $employe->telephone) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Adresse E-mail</label>
                            <input type="email" class="form-control" name="email"
                                value="{{ old('email', $employe->email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Adresse / Quartier</label>
                            <input type="text" class="form-control" name="adresse"
                                value="{{ old('adresse', $employe->adresse) }}">
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <h5 class="text-primary fw-bold mb-3"><i class="bi bi-briefcase me-2"></i>3. Fonction & Contrat</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fonction / Poste <span
                                    class="text-danger">*</span></label>
                            <select name="fonction_id" class="form-select" required>
                                <option value="">Sélectionner une fonction...</option>
                                @foreach ($fonctions as $fonction)
                                    <option value="{{ $fonction->id }}"
                                        {{ old('fonction_id', $employe->fonction_id) == $fonction->id ? 'selected' : '' }}>
                                        {{ $fonction->libelle }} (Def:
                                        {{ number_format($fonction->salaire_base_defaut, 0, ',', ' ') }} F)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Salaire de base personnalisé (F CFA)</label>
                            <input type="number" class="form-control" name="salaire_base"
                                value="{{ old('salaire_base', $employe->salaire_base) }}"
                                placeholder="Vide = utilise la fonction">
                            <div class="form-text small">Actuel effectif :
                                {{ number_format($employe->salaire_effectif, 0, ',', ' ') }} F CFA</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Statut du contrat <span
                                    class="text-danger">*</span></label>
                            <select name="statut_contrat" class="form-select" required>
                                <option value="essai"
                                    {{ old('statut_contrat', $employe->statut_contrat) == 'essai' ? 'selected' : '' }}>
                                    Période d'essai</option>
                                <option value="confirme"
                                    {{ old('statut_contrat', $employe->statut_contrat) == 'confirme' ? 'selected' : '' }}>
                                    Confirmé</option>
                                <option value="cdd"
                                    {{ old('statut_contrat', $employe->statut_contrat) == 'cdd' ? 'selected' : '' }}>CDD
                                </option>
                                <option value="cdi"
                                    {{ old('statut_contrat', $employe->statut_contrat) == 'cdi' ? 'selected' : '' }}>CDI
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date d'embauche <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_embauche"
                                value="{{ old('date_embauche', optional($employe->date_embauche)->format('Y-m-d')) }}"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pièce d'identité (CNI / Passeport)</label>
                            <input type="text" class="form-control" name="piece_identite"
                                value="{{ old('piece_identite', $employe->piece_identite) }}">
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <h5 class="text-primary fw-bold mb-3"><i class="bi bi-shield-exclamation me-2"></i>4. Contact en cas
                        d'urgence</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nom de la personne à contacter</label>
                            <input type="text" class="form-control" name="contact_urgence_nom"
                                value="{{ old('contact_urgence_nom', $employe->contact_urgence_nom) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Téléphone d'urgence</label>
                            <input type="text" class="form-control" name="contact_urgence_telephone"
                                value="{{ old('contact_urgence_telephone', $employe->contact_urgence_telephone) }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.employes.index') }}" class="btn btn-light px-4">Annuler</a>
                        <button type="submit" class="btn btn-primary px-5">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
