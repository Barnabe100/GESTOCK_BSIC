@extends('includes.master')

@section('title', 'Détail demande')

@section('content')

    <div class="container-fluid mt-4">

        <!-- 🔹 INFOS DEMANDE -->
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5>Détail demande</h5>

                @if ($demande->user_id == auth()->id())
                    <a href="{{ route('mes.demandes') }}" class="btn btn-light btn-sm">
                        Retour
                    </a>
                @endif
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Objet</label>
                        <div class="form-control bg-light">
                            {{ $demande->objet }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Date</label>
                        <div class="form-control bg-light">
                            {{ $demande->date }}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Utilisateur</label>
                        <div class="form-control bg-light">
                            {{ $demande->user->name }} {{ $demande->user->prenom }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Statut</label>

                        <div class="form-control bg-light">



                            @include('includes.statut-demande')



                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12 mb-3">
                        <label>Description</label>
                        <div class="form-control bg-light">
                            {{ $demande->description ?? 'Aucune description fournie' }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

        @php

            $acteur = null;
            $motif = null;

            if (!empty($demande->commentaire_validation_finale)) {
                $acteur = 'Demandeur';
                $motif = $demande->commentaire_validation_finale;
            } elseif (!empty($demande->commentaire_gestionnaire)) {
                $acteur = 'Gestionnaire';
                $motif = $demande->commentaire_gestionnaire;
            } elseif (!empty($demande->commentaire_superieur)) {
                $acteur = 'Supérieur hiérarchique';
                $motif = $demande->commentaire_superieur;
            }

        @endphp

        @if ($motif)
            <div class="alert alert-danger mt-3">

                <strong>
                    Motif du rejet : ({{ $acteur }})
                </strong>

                <hr>

                {{ $motif }}

            </div>
        @endif

        @if (auth()->user()->hasAnyRole(['Gestionnaire', 'Admin', 'SuperAdmin']))
            @if ($demande->validation_superieur || $demande->validation_gestionnaire || $demande->validation_finale)

                <div class="card mt-3 border-info">

                    <div class="card-header bg-info text-white">

                        Historique des validations

                    </div>

                    <div class="card-body">

                        <div class="row">

                            @if ($demande->validation_superieur)
                                <div class="col-md-6 mb-3">

                                    <label>Supérieur ayant validé</label>

                                    <div class="form-control bg-light">

                                        {{ $demande->superieur->prenom ?? '' }}
                                        {{ $demande->superieur->name ?? '' }}

                                    </div>

                                </div>

                                <div class="col-md-6 mb-3">

                                    <label>Date validation supérieur</label>

                                    <div class="form-control bg-light">

                                        {{ $demande->date_validation_superieur }}

                                    </div>

                                </div>
                            @endif

                            @if ($demande->validation_gestionnaire)
                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <label>Gestionnaire ayant validé</label>

                                        <div class="form-control bg-light">

                                            {{ $demande->gestionnaire->prenom ?? '' }}
                                            {{ $demande->gestionnaire->name ?? '' }}

                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <label>Date validation gestionnaire</label>

                                        <div class="form-control bg-light">

                                            {{ $demande->date_validation_gestionnaire }}

                                        </div>

                                    </div>

                                </div>
                            @endif

                            @if ($demande->validation_finale)
                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <label>Validateur final</label>

                                        <div class="form-control bg-light">

                                            {{ $demande->validateurFinal->prenom ?? '' }}
                                            {{ $demande->validateurFinal->name ?? '' }}

                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <label>Date validation finale</label>

                                        <div class="form-control bg-light">

                                            {{ $demande->date_validation_finale }}

                                        </div>

                                    </div>

                                </div>
                            @endif

                        </div>

                    </div>

                </div>

            @endif
        @endif



        @if ($demande->commentaire_reception)
            <div class="alert alert-info">

                <b>

                    Retour demandeur :

                </b>

                {{ $demande->commentaire_reception }}

            </div>
        @endif

        @if ($demande->commentaire_validation_finale && !$demande->validation_finale)
            <div class="alert alert-danger">

                <strong>

                    Motif du rejet final :

                </strong>

                {{ $demande->commentaire_validation_finale }}

            </div>
        @endif

        <!-- 🔥 AJOUT ARTICLE -->
        <div class="card mt-4 shadow-sm">

            <div class="card-header bg-secondary text-white">
                Ajouter un article
            </div>


            <div class="card-body">

                @if (!$demande->validation_superieur)

                    <form method="POST" action="{{ route('article_demande.store') }}">
                        @csrf

                        <input type="hidden" name="demande_id" value="{{ $demande->id }}">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label>Article</label>
                                <select name="article_id" class="form-control select2">

                                    @foreach ($articles as $article)
                                        <option value="{{ $article->id }}">
                                            {{ $article->libelle }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Quantité</label>
                                <input type="number" name="quantite" class="form-control" required>
                            </div>

                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                @if ($demande->user_id == auth()->id())
                                    <button class="btn btn-success">
                                        Ajouter
                                    </button>
                                @endif
                            </div>

                        </div>

                    </form>
                @else
                    <div class="alert alert-warning">
                        ⚠️ Demande déjà validée — modification impossible
                    </div>

                @endif

            </div>

        </div>

        <!-- 🔥 LISTE DES ARTICLES -->
        <div class="card mt-4 shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                Articles demandés
                {{-- @if (!($estMesDemandes ?? false))
                    <div class="d-flex gap-2">
                        <!-- EXCEL -->
                        <a href="{{ route('articles-demandes.export.excel') }}" class="btn btn-success btn-sm">

                            <i class="fas fa-file-excel"></i>
                            Excel
                        </a>

                        <!-- CSV -->
                        <a href="{{ route('articles-demandes.export.csv') }}" class="btn btn-info btn-sm">

                            <i class="fas fa-file-csv"></i>
                            CSV
                        </a>
                    </div>
                @endif --}}
            </div>



            <div class="card-body">

                <table class="table table-bordered table-striped">

                    <thead style="background-color: #c3c3ee;">
                        <tr>
                            <th>#</th>
                            <th>Article</th>
                            <th>Quantité demandée</th>
                            <th>Quantité accordée</th>
                            <th width="120px">Actions</th>
                        </tr>
                    </thead>



                    <tbody>

                        @forelse($demande->articles as $key => $ligne)
                            <tr>

                                <td>{{ $key + 1 }}</td>

                                <td>{{ $ligne->article->libelle }}</td>

                                <td>{{ $ligne->quantite }}</td>

                                <td>

                                    @if ($ligne->quantite_accordee)
                                        <span class="badge bg-success">

                                            {{ $ligne->quantite_accordee }}

                                        </span>
                                    @else
                                        <span class="text-muted">

                                            -

                                        </span>
                                    @endif

                                </td>



                                <td>

                                    @if (!$demande->validation_superieur && $demande->user_id == auth()->id())
                                        <form action="{{ route('article_demande.destroy', $ligne->id) }}" method="POST"
                                            style="display:inline-block">

                                            @csrf
                                            @method('DELETE')


                                            <button type="button" title="Supprimer" class="btn btn-danger btn-sm"
                                                onclick="confirmDelete(this.form)">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </form>

                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#editArticleDemande{{ $ligne->id }}" title="Modifier">

                                            <i class="fas fa-edit"></i>

                                        </button>
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @endif

                                    @role(['Gestionnaire'])
                                        @if ($demande->validation_gestionnaire && !$demande->validation_finale)
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#validationModal{{ $demande->id }}"
                                                title="Modifier quantité accordeé">

                                                <i class="fas fa-edit"></i>

                                            </button>
                                        @endif
                                    @endrole

                                    {{-- Modal modification article demandé --}}
                                    <div class="modal fade" id="editArticleDemande{{ $ligne->id }}" tabindex="-1">

                                        <div class="modal-dialog">

                                            <div class="modal-content">

                                                <div class="modal-header">

                                                    <h5>Modifier article demandé</h5>

                                                </div>

                                                <form action="{{ route('article-demandes.update', $ligne->id) }}"
                                                    method="POST">

                                                    @csrf
                                                    @method('PUT')

                                                    <div class="modal-body">

                                                        <div class="mb-3">

                                                            <label>Article</label>

                                                            <select name="article_id" class="form-control article-select2"
                                                                required>

                                                                @foreach ($articles as $article)
                                                                    <option value="{{ $article->id }}"
                                                                        {{ $ligne->article_id == $article->id ? 'selected' : '' }}>
                                                                        {{ $article->libelle }}
                                                                    </option>
                                                                @endforeach

                                                            </select>

                                                        </div>

                                                        <div class="mb-3">

                                                            <label>Quantité</label>

                                                            <input type="number" name="quantite" class="form-control"
                                                                value="{{ $ligne->quantite }}" min="1" required>

                                                        </div>

                                                    </div>

                                                    <div class="modal-footer">

                                                        <button type="submit" class="btn btn-primary">

                                                            Enregistrer

                                                        </button>

                                                    </div>

                                                </form>

                                            </div>

                                        </div>

                                    </div>
                                    {{-- Debut modification quantité accordée --}}
                                    <div class="modal fade" id="validationModal{{ $demande->id }}" tabindex="-1">

                                        <div class="modal-dialog modal-lg">

                                            <div class="modal-content">

                                                <div class="modal-header bg-success text-white">

                                                    <h5>

                                                        Modifier la quantité accordée

                                                    </h5>

                                                    <button class="btn-close" data-bs-dismiss="modal">

                                                    </button>

                                                </div>

                                                <form action="{{ route('demandes.valider', $demande->id) }}"
                                                    method="POST">

                                                    @csrf
                                                    @method('PUT')

                                                    <div class="modal-body">

                                                        <table class="table">

                                                            <thead>

                                                                <tr>

                                                                    <th>

                                                                        Article

                                                                    </th>

                                                                    <th>

                                                                        Demandée

                                                                    </th>

                                                                    <th>

                                                                        Disponible

                                                                    </th>

                                                                    <th>

                                                                        Accordée

                                                                    </th>

                                                                </tr>

                                                            </thead>

                                                            <tbody>

                                                                @foreach ($demande->articles as $item)
                                                                    <tr>

                                                                        <td>

                                                                            {{ $item->article->libelle }}

                                                                        </td>

                                                                        <td>

                                                                            {{ $item->quantite }}

                                                                        </td>

                                                                        <td>

                                                                            {{ $item->article->stock_disponible }}

                                                                        </td>

                                                                        <td>

                                                                            <input type="number"
                                                                                name="quantite_accordee[{{ $item->article->id }}]"
                                                                                class="form-control" min="0"
                                                                                max="{{ min($item->quantite, $item->article->stock_disponible) }}"
                                                                                value="{{ $item->quantite_accordee ?? min($item->quantite, $item->article->stock_disponible) }}">

                                                                        </td>

                                                                    </tr>
                                                                @endforeach

                                                            </tbody>

                                                        </table>

                                                    </div>

                                                    <div class="modal-footer">

                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">

                                                            Annuler

                                                        </button>

                                                        <button type="submit" class="btn btn-success">

                                                            Valider

                                                        </button>

                                                    </div>

                                                </form>

                                            </div>

                                        </div>

                                    </div>
                                    {{-- Fin modification quantité accordée --}}

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center">
                                    Aucun article ajouté
                                </td>
                            </tr>
                        @endforelse

                    </tbody>



                </table>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/select.js') }}"></script>
@endpush


<script>
    $(document).on('shown.bs.modal', '.modal', function() {

        $(this).find('.article-select2').select2({

            dropdownParent: $(this),

            width: '100%'

        });

    });

    $(document).on('shown.bs.modal', '.modal', function() {

        $(this).find('.article-select2').each(function() {

            if ($(this).hasClass('select2-hidden-accessible')) {

                $(this).select2('destroy');

            }

            $(this).select2({

                dropdownParent: $(this).closest('.modal'),

                width: '100%'

            });

        });

    });
</script>
