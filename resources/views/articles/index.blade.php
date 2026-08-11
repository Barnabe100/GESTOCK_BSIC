@extends('includes.master')

@section('title', 'Liste des articles')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            {{-- {{ $article->cmp }} --}}

            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0">Liste des articles</h5>

                <div class="d-flex gap-2">

                    <!-- EXCEL -->
                    <a href="{{ route('articles.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>

                    <!-- CSV -->
                    <a href="{{ route('articles.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>

                    <a href="{{ route('articles.create') }}" class="btn btn-light btn-sm">
                        + Ajouter
                    </a>
                </div>
            </div>

            <!-- BODY -->
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">

                        <thead style="background-color: #c3c3ee;">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Libellé</th>
                                <th>Catégorie</th>
                                <th>Type article</th>
                                <th>Paramétrage</th>
                                {{-- <th>Prix</th>
                                <th>Stock disponible</th>
                                <th>Statut stock</th> --}}

                                <th width="180px">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($articles as $key => $article)
                                <tr>

                                    <td>{{ $key + 1 }}</td>

                                    <td>{{ $article->code }}</td>

                                    <td>{{ $article->libelle }}</td>
                                    <td>
                                        {{ $article->categorie->libelle ?? '' }}
                                    </td>
                                    <td>
                                        {{ $article->typeArticle->libelle ?? '' }}
                                    </td>

                                    <td class="text-center">

                                        @if (is_null($article->prix_unitaire) || is_null($article->stock_initial) || is_null($article->stock_securite))
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle"></i>
                                                {{-- Non paramétré --}}
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i>
                                                {{-- Paramétré --}}
                                            </span>
                                        @endif

                                    </td>

                                    {{-- <td>

                                        @if ($article->prix_unitaire === null)
                                            <span class="badge bg-secondary">
                                                Non paramétré
                                            </span>
                                        @else
                                            {{ number_format($article->prix_unitaire, 2, ',', ' ') }}
                                        @endif

                                    </td> --}}

                                    <!-- STOCK AVEC COULEUR -->
                                    {{-- <td>

                                        @if ($article->prix_unitaire === null)
                                            <span class="badge bg-secondary">
                                                Non paramétré
                                            </span>
                                        @elseif($article->stock_disponible <= $article->stock_securite)
                                            <span class="badge bg-danger">

                                                {{ $article->stock_disponible }}

                                            </span>
                                        @else
                                            <span class="badge bg-success">

                                                {{ $article->stock_disponible }}

                                            </span>
                                        @endif

                                    </td> --}}

                                    {{-- <td>

                                        @if ($article->prix_unitaire === null)
                                            <span class="badge bg-secondary">

                                                Non paramétré

                                            </span>
                                        @elseif($article->stock_disponible <= $article->stock_securite)
                                            <span class="badge bg-danger">

                                                <i class="fas fa-exclamation-triangle"></i>

                                                Critique

                                            </span>
                                        @elseif($article->stock_disponible <= $article->stock_securite + 5)
                                            <span class="badge bg-warning text-dark">

                                                ⚠ Faible

                                            </span>
                                        @else
                                            <span class="badge bg-success">

                                                <i class="fas fa-check"></i>

                                                OK

                                            </span>
                                        @endif

                                    </td> --}}



                                    <!-- ACTIONS -->
                                    <td>



                                        <a href="#" class="btn btn-primary btn-sm btn-stock" data-bs-toggle="modal"
                                            data-bs-target="#stockModal"
                                            data-url="{{ route('articles.update', $article->id) }}"
                                            data-id="{{ $article->id }}" data-code="{{ $article->code }}"
                                            data-libelle="{{ $article->libelle }}"
                                            data-categorie="{{ optional($article->categorie)->libelle }}"
                                            data-type="{{ optional($article->typeArticle)->libelle }}"
                                            data-prix="{{ $article->prix_unitaire }}"
                                            data-stockinitial="{{ $article->stock_initial }}"
                                            data-stock="{{ $article->stock_disponible }}"
                                            data-securite="{{ $article->stock_securite }}" title="Poursuivre ...">

                                            <i class="fas fa-cog"></i>


                                        </a>

                                        <!-- VOIR -->
                                        <a href="{{ route('articles.show', $article->id) }}" title="Détail"
                                            class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- MODIFIER -->
                                        <a href="{{ route('articles.edit', $article->id) }}" title="Editer"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#rebut{{ $article->id }}" title="Mise au rebut">

                                            <i class="fas fa-archive"></i>



                                        </button>

                                        <!-- SUPPRIMER -->
                                        <form action="{{ route('articles.destroy', $article->id) }}" method="POST"
                                            style="display:inline-block">

                                            @csrf
                                            @method('DELETE')

                                            <button type="button" title="Supprimer" class="btn btn-danger btn-sm"
                                                onclick="confirmDelete(this.form)">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </form>



                                        <div class="modal fade" id="rebut{{ $article->id }}">

                                            <div class="modal-dialog">

                                                <div class="modal-content">

                                                    <div class="modal-header bg-danger text-white">

                                                        <h5>

                                                            Mise au rebut

                                                        </h5>

                                                    </div>

                                                    <form action="{{ route('articles.rebut', $article->id) }}"
                                                        method="POST">

                                                        @csrf

                                                        @method('PUT')

                                                        <div class="modal-body">

                                                            <label>

                                                                Quantité

                                                            </label>

                                                            <input type="number" name="quantite" min="1"
                                                                class="form-control" required>


                                                            <label class="mt-2">

                                                                Date rebut

                                                            </label>

                                                            <input type="date" name="date_rebut" class="form-control"
                                                                required value="{{ date('Y-m-d') }}">


                                                            <label class="mt-2">

                                                                Motif

                                                            </label>

                                                            <textarea name="motif" class="form-control" required></textarea>


                                                        </div>
                                                    </form>


                                                    <div class="modal-footer">

                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">

                                                            Annuler

                                                        </button>


                                                        <button class="btn btn-danger">

                                                            Valider

                                                        </button>

                                                    </div>

                                                    </form>

                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            </div>

        </div>

    </div>


    <!-- ===================== MODAL GESTION DU STOCK ===================== -->

    <div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered">

            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title" id="stockModalLabel">

                        <i class="fas fa-boxes"></i>

                        Gestion du stock

                    </h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                    </button>

                </div>

                <!-- BODY -->
                <form id="formStock" method="POST">

                    @csrf
                    @method('PUT')

                    <div class="modal-body">

                        <input type="hidden" id="articleId">
                        <input type="hidden" id="modeStock">

                        <!-- ====================================================== -->
                        <!-- INFORMATIONS ARTICLE -->
                        <!-- ====================================================== -->

                        <div class="card border-secondary shadow-sm mb-4">

                            <div class="card-header bg-secondary text-white">

                                <i class="fas fa-info-circle"></i>

                                Informations de l'article

                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Code

                                        </label>

                                        <input type="text" id="infoCode" class="form-control bg-light" readonly>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Libellé

                                        </label>

                                        <input type="text" id="infoLibelle" class="form-control bg-light" readonly>

                                    </div>

                                </div>

                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Catégorie

                                        </label>

                                        <input type="text" id="infoCategorie" class="form-control bg-light" readonly>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Type d'article

                                        </label>

                                        <input type="text" id="infoType" class="form-control bg-light" readonly>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- ====================================================== -->
                        <!-- INFORMATIONS STOCK -->
                        <!-- ====================================================== -->

                        <div class="card border-success shadow-sm">

                            <div class="card-header bg-success text-white">

                                <i class="fas fa-warehouse"></i>

                                Informations de stock

                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Prix unitaire

                                        </label>

                                        <input type="number" id="prixUnitaire" name="prix_unitaire"
                                            class="form-control">

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Stock initial

                                        </label>

                                        <input type="number" id="stockInitial" name="stock_initial"
                                            class="form-control">

                                    </div>

                                </div>

                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Stock disponible

                                        </label>

                                        <input type="number" id="stockDisponible" name="stock_disponible"
                                            class="form-control bg-light">

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Stock de sécurité

                                        </label>

                                        <input type="number" id="stockSecurite" name="stock_securite"
                                            class="form-control">

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- FOOTER -->

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Fermer
                        </button>

                        <button type="submit" id="btnStock" class="btn btn-success">
                            Ajouter
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
@endsection


@push('scripts')
    <script>
        $(document).ready(function() {

            $('.btn-stock').on('click', function() {

                let url = $(this).data('url');
                let id = $(this).data('id');

                let code = $(this).data('code');
                let libelle = $(this).data('libelle');
                let categorie = $(this).data('categorie');
                let type = $(this).data('type');

                let prix = $(this).data('prix');
                let stockInitial = $(this).data('stockinitial');
                let stockDisponible = $(this).data('stock');
                let stockSecurite = $(this).data('securite');

                // Action du formulaire
                $('#formStock').attr('action', url);

                // Informations article
                $('#articleId').val(id);
                $('#infoCode').val(code);
                $('#infoLibelle').val(libelle);
                $('#infoCategorie').val(categorie);
                $('#infoType').val(type);

                // Informations stock
                $('#prixUnitaire').val(prix);
                $('#stockInitial').val(stockInitial);
                $('#stockDisponible').val(stockDisponible);
                $('#stockSecurite').val(stockSecurite);

                // Bouton Ajouter / Modifier
                if (prix == null || prix == '' || prix == 0) {

                    $('#modeStock').val('create');

                    $('#btnStock')
                        .removeClass('btn-warning')
                        .addClass('btn-success')
                        .html('<i class="fas fa-plus-circle"></i> Ajouter');

                } else {

                    $('#modeStock').val('update');

                    $('#btnStock')
                        .removeClass('btn-success')
                        .addClass('btn-warning')
                        .html('<i class="fas fa-edit"></i> Modifier');

                }

            });

        });
    </script>
@endpush
