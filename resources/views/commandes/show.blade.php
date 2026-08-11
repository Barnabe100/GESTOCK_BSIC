@extends('includes.master')

@section('title', 'Détail commande')

@section('content')

    <div class="container-fluid mt-4">

        <!-- 🔹 INFOS COMMANDE -->
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5>Détail commande</h5>
                @if ($commande->user_id == auth()->id())
                    <a href="{{ route('commandes.index') }}" class="btn btn-light btn-sm">
                        ← Retour
                    </a>
                @endif
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Numéro de bon de commande</label>
                        <div class="form-control bg-light">
                            {{ $commande->numero_bon_commande }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Fournisseur</label>
                        <div class="form-control bg-light">
                            {{ $commande->fournisseur->nom ?? '-' }}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12 mb-3">
                        <label>Libellé</label>
                        <div class="form-control bg-light">
                            {{ $commande->libelle }}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Date</label>
                        <div class="form-control bg-light">
                            {{ $commande->date }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Statut</label>
                        <div class="form-control bg-light">

                            @if ($commande->validation)
                                <span class="badge bg-success">Validée</span>
                            @else
                                <span class="badge bg-warning">En attente</span>
                            @endif

                            @if ($commande->livraison)
                                <span class="badge bg-primary">Receptionnée</span>
                            @endif

                        </div>
                    </div>



                    <div class="row mb-3">

                        <div class="col-md-4 mb-3">
                            <label>Montant total</label>
                            <div class="form-control bg-light">

                                {{ number_format($commande->montant, 2, '.', ' ') }}

                            </div>
                        </div>
                        <div class="col-md-4">

                            <label>Montant utilisé</label>

                            <div class="form-control bg-light text-success fw-bold">

                                {{ number_format($commande->articles->sum(fn($a) => $a->quantite * $a->prix_unitaire), 2, '.', ' ') }}

                            </div>

                        </div>

                        <div class="col-md-4">

                            <label>Reste disponible</label>

                            <div class="form-control bg-light text-danger fw-bold">

                                {{ number_format(
                                    $commande->montant - $commande->articles->sum(fn($a) => $a->quantite * $a->prix_unitaire),
                                
                                    2,
                                    '.',
                                    ' ',
                                ) }}

                            </div>

                        </div>

                    </div>



                </div>



            </div>

        </div>


        @hasanyrole('Gestionnaire|SuperAdmin')
            <!-- 🔥 AJOUT ARTICLE -->
            <div class="card mt-4 shadow-sm">

                <div class="card-header bg-secondary text-white">
                    Ajouter un article
                </div>

                <div class="card-body">

                    @if (!$commande->validation)
                        <form method="POST" action="{{ route('article_commande.store') }}">
                            @csrf

                            <input type="hidden" name="commande_id" value="{{ $commande->id }}">

                            <div class="row">

                                <div class="col-md-4 mb-3">
                                    <label>Article</label>
                                    <select name="article_id" id="article_id" class="form-control select2">
                                        <option value=""></option>
                                        @foreach ($articles as $article)
                                            <option value="{{ $article->id }}" data-prix="{{ $article->prix_unitaire }}">
                                                {{ $article->libelle }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- <div class="col-md-2 mb-3">
                                    <label>Prix initial de l'article</label>
                                    <input type="number" id="prix_unitaire" name="prix_unitaire" class="form-control" required>
                                </div> --}}

                                <div class="col-md-2 mb-3">
                                    <label>Prix unitaire</label>
                                    <input step="0.01" type="number" step="0.01" id="prix_unitaire" name="prix_unitaire"
                                        class="form-control" required>

                                </div>


                                <div class="col-md-2 mb-3">
                                    <label>Quantité</label>
                                    <input type="number" id="quantite" name="quantite" class="form-control" required>
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label>Montant</label>
                                    <input type="text" id="montant" class="form-control bg-light" readonly>

                                    <small id="resteApresAjout" class="fw-bold text-success" style="min-height:20px;"> </small>
                                </div>








                                {{-- <div class="col-md-2 mb-3">
                                    <label>Prix unitaire</label>
                                    <input type="number" name="prix_unitaire" class="form-control" required>
                                </div> --}}



                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    @if (
                                        $commande->user_id == auth()->id() ||
                                            auth()->user()->hasAnyRole(['SuperAdmin', 'Gestionnaire']))
                                        <button id="btnAjouterArticle" class="btn btn-success w-100">
                                            Ajouter
                                        </button>
                                    @endif

                                </div>

                            </div>



                        </form>
                    @else
                        <div class="alert alert-warning">
                            🔒 Commande validée — modification impossible
                        </div>
                    @endif

                </div>

            </div>
        @endhasanyrole

        <!-- 🔥 LISTE DES ARTICLES -->
        <div class="card mt-4 shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                Articles de la commande

                {{-- <div class="d-flex gap-2">
                    <!-- EXCEL -->
                    <a href="{{ route('articles-commandes.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>

                    <!-- CSV -->
                    <a href="{{ route('articles-commandes.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>
                </div> --}}
            </div>

            <div class="card-body">

                <table class="table table-bordered table-striped">

                    <thead style="background-color: #c3c3ee;">
                        <tr>
                            <th>#</th>
                            <th>Article</th>
                            <th>Quantité commandée</th>
                            <th>Quantité livrée</th>
                            <th>Quantité restante</th>
                            <th>Prix unitaire</th>
                            <th>Montant total</th>
                            <th>Statut</th>
                            @hasanyrole('Gestionnaire|SuperAdmin')
                                <th width="120px">Actions</th>
                            @endhasanyrole
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($commande->articles as $key => $ligne)
                            <tr>

                                <td>{{ $key + 1 }}</td>

                                <td>{{ $ligne->article->libelle }}</td>

                                <td>{{ $ligne->quantite }}</td>
                                <td>{{ $ligne->quantite_livree }}</td>

                                <td>

                                    @if ($ligne->quantite_restante > 0)
                                        <span class="badge bg-warning">

                                            {{ $ligne->quantite_restante }}

                                        </span>
                                    @else
                                        <span class="badge bg-success">

                                            0

                                        </span>
                                    @endif

                                </td>

                                {{-- <td>{{ $ligne->prix_unitaire }}</td> --}}

                                <td>

                                    {{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}


                                </td>

                                {{-- <td>
                                    {{ $ligne->quantite * $ligne->prix_unitaire }}
                                </td> --}}

                                <td>

                                    {{ number_format($ligne->quantite * $ligne->prix_unitaire, 0, ',', ' ') }}

                                </td>

                                <td>

                                    @if ($ligne->quantite_livree == 0)
                                        <span class="badge bg-danger">

                                            En attente

                                        </span>
                                    @elseif($ligne->quantite_restante > 0)
                                        <span class="badge bg-warning">

                                            Partielle

                                        </span>
                                    @else
                                        <span class="badge bg-success">

                                            Receptionnée

                                        </span>
                                    @endif

                                </td>

                                @hasanyrole('Gestionnaire|SuperAdmin')
                                    <td>


                                        @if (!$commande->validation)
                                            <form method="POST" action="{{ route('article_commande.destroy', $ligne->id) }}"
                                                style="display:inline-block">

                                                @csrf
                                                @method('DELETE')

                                                <button type="button" title="Supprimer" class="btn btn-danger btn-sm"
                                                    onclick="confirmDelete(this.form)">
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                            </form>
                                        @else
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @endif

                                        @if (!$commande->validation)
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#editArticleCommande{{ $ligne->id }}">

                                                <i class="fas fa-edit"></i>

                                            </button>
                                        @else
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @endif


                                        <div class="modal fade" id="editArticleCommande{{ $ligne->id }}" tabindex="-1">

                                            <div class="modal-dialog">

                                                <div class="modal-content">

                                                    <div class="modal-header">

                                                        <h5>

                                                            Modifier ligne

                                                        </h5>

                                                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                                                        </button>

                                                    </div>

                                                    <form action="{{ route('article-commandes.update', $ligne->id) }}"
                                                        method="POST">

                                                        @csrf
                                                        @method('PUT')

                                                        <div class="modal-body">

                                                            <div class="mb-3">
                                                                <label>Article</label>
                                                                <input type="text" class="form-control bg-light"
                                                                    value="{{ $ligne->article->libelle }}" readonly>
                                                            </div>

                                                            <div class="row">

                                                                <div class="col-md-6 mb-3">
                                                                    <label>Quantité</label>

                                                                    <input type="number" name="quantite"
                                                                        id="quantiteEdit{{ $ligne->id }}"
                                                                        class="form-control" value="{{ $ligne->quantite }}"
                                                                        required>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <label>Prix unitaire</label>

                                                                    <input type="number" name="prix_unitaire"
                                                                        id="prixEdit{{ $ligne->id }}" class="form-control"
                                                                        value="{{ $ligne->prix_unitaire }}" required>
                                                                </div>

                                                            </div>

                                                            <div class="row">

                                                                <div class="col-md-6 mb-3">

                                                                    <label>Montant</label>

                                                                    <input type="text" id="montantEdit{{ $ligne->id }}"
                                                                        class="form-control bg-light"
                                                                        value="{{ number_format($ligne->quantite * $ligne->prix_unitaire, 0, ',', ' ') }}"
                                                                        readonly>

                                                                </div>

                                                                <div class="col-md-6 mb-3 d-flex align-items-end">

                                                                    <small id="resteEdit{{ $ligne->id }}" class="fw-bold">
                                                                    </small>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div class="modal-footer">

                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">
                                                                Fermer
                                                            </button>

                                                            <button type="submit" id="btnEdit{{ $ligne->id }}"
                                                                class="btn btn-primary">
                                                                Enregistrer
                                                            </button>

                                                        </div>

                                                    </form>

                                                </div>

                                            </div>

                                        </div>

                                    </td>
                                @endhasanyrole

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6" class="text-center">
                                    Aucun article ajouté
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>

                @php
                    $montantUtilise = $commande->articles->sum(function ($ligne) {
                        return $ligne->quantite * $ligne->prix_unitaire;
                    });
                @endphp

                @if ($commande->statut == 'En préparation' && $commande->articles->isNotEmpty() && $montantUtilise == $commande->montant)
                    <div class="mt-3">

                        <form action="{{ route('commandes.soumettre', $commande->id) }}" method="POST">

                            @csrf
                            @method('PUT')

                            <button type="button" class="btn btn-primary"
                                onclick="confirmAction(
                        this.form,
                        'Soumettre cette commande pour validation ?'
                    )">

                                {{-- <i class="fas fa-paper-plane"></i> --}}

                                Soumettre la commande

                            </button>

                        </form>

                    </div>
                @endif

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $('.select2').select2({
                placeholder: 'Sélectionner ...',
                allowClear: true,
                width: '100%'
            });

        });
    </script>

    {{-- <script>
        $(document).ready(function() {

            $('#article_id').on('change', function() {

                let prix = $(this)
                    .find(':selected')
                    .data('prix');

                $('#prix_unitaire').val(prix ?? '');



            });

        });
    </script> --}}

    <script>
        $(document).ready(function() {
            let montantCommande = {{ $commande->montant }};
            let montantUtilise = {{ $commande->articles->sum(fn($a) => $a->quantite * $a->prix_unitaire) }};

            function calculerMontant() {

                if (!$('#article_id').val()) {

                    $('#montant').val('');
                    $('#resteApresAjout').text('');
                    $('#btnAjouterArticle').prop('disabled', false);

                    return;
                }

                let quantite = parseFloat($('#quantite').val()) || 0;
                let prix = parseFloat($('#prix_unitaire').val()) || 0;

                let montant = quantite * prix;

                $('#montant').val(
                    montant.toLocaleString('fr-FR', {
                        minimumFractionDigits: 0
                    })
                );

                let reste = montantCommande - montantUtilise - montant;

                if (reste >= 0) {

                    $('#btnAjouterArticle')
                        .prop('disabled', false);

                    $('#resteApresAjout')
                        .removeClass('text-danger')
                        .addClass('text-success')
                        .text(
                            'Reste montant : ' +
                            reste.toLocaleString('fr-FR')
                        );

                } else {

                    $('#btnAjouterArticle').prop('disabled', true);

                    $('#resteApresAjout')
                        .removeClass('text-success')
                        .addClass('text-danger')
                        .text(
                            '⚠ Dépassement : ' +
                            Math.abs(reste).toLocaleString('fr-FR')
                        );

                }

            }

            // Choix de l'article
            $('#article_id').on('change', function() {

                let prix = $(this)
                    .find(':selected')
                    .data('prix');

                if ($(this).val()) {

                    $('#prix_unitaire').val(prix ?? '');

                } else {

                    // Réinitialisation
                    $('#prix_unitaire').val('');
                    $('#quantite').val('');
                    $('#montant').val('');
                    $('#resteApresAjout').text('');

                }

                calculerMontant();

            });



            // Modification de la quantité
            $('#quantite').on('input', calculerMontant);

            // Modification du prix unitaire
            $('#prix_unitaire').on('input', calculerMontant);

        });

        @foreach ($commande->articles as $ligne)

            (function() {

                let montantCommande = {{ $commande->montant }};
                let montantUtilise = {{ $commande->articles->sum(fn($a) => $a->quantite * $a->prix_unitaire) }};
                let ancienMontant = {{ $ligne->quantite * $ligne->prix_unitaire }};

                function calculEdit() {

                    let quantite = parseFloat($('#quantiteEdit{{ $ligne->id }}').val()) || 0;
                    let prix = parseFloat($('#prixEdit{{ $ligne->id }}').val()) || 0;

                    let nouveauMontant = quantite * prix;

                    $('#montantEdit{{ $ligne->id }}').val(
                        nouveauMontant.toLocaleString('fr-FR')
                    );

                    let reste = montantCommande - (montantUtilise - ancienMontant + nouveauMontant);

                    if (reste >= 0) {

                        $('#btnEdit{{ $ligne->id }}').prop('disabled', false);

                        $('#resteEdit{{ $ligne->id }}')
                            .removeClass('text-danger')
                            .addClass('text-success')
                            .text(
                                'Reste montant : ' +
                                reste.toLocaleString('fr-FR')
                            );

                    } else {

                        $('#btnEdit{{ $ligne->id }}').prop('disabled', true);

                        $('#resteEdit{{ $ligne->id }}')
                            .removeClass('text-success')
                            .addClass('text-danger')
                            .text(
                                '⚠ Dépassement : ' +
                                Math.abs(reste).toLocaleString('fr-FR')
                            );

                    }

                }

                $('#quantiteEdit{{ $ligne->id }}').on('input', calculEdit);

                let quantiteInitiale = {{ $ligne->quantite }};
                let prixInitial = {{ $ligne->prix_unitaire }};

                $('#prixEdit{{ $ligne->id }}').on('input', calculEdit);

                calculEdit();

                $('#editArticleCommande{{ $ligne->id }}').on('hidden.bs.modal', function() {

                    // Remettre les valeurs initiales
                    $('#quantiteEdit{{ $ligne->id }}').val(quantiteInitiale);
                    $('#prixEdit{{ $ligne->id }}').val(prixInitial);

                    // Recalculer le montant et le reste
                    calculEdit();

                });

            })();
        @endforeach
    </script>
@endpush
