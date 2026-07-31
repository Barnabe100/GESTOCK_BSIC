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
                                <th>Prix</th>
                                <th>Stock disponible</th>
                                <th>Statut stock</th>

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

                                    <td>

                                        @if ($article->prix_unitaire === null)
                                            <span class="badge bg-secondary">
                                                Non paramétré
                                            </span>
                                        @else
                                            {{ number_format($article->prix_unitaire, 2, ',', ' ') }}
                                        @endif

                                    </td>

                                    <!-- STOCK AVEC COULEUR -->
                                    <td>

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

                                    </td>

                                    <td>

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

                                    </td>



                                    <!-- ACTIONS -->
                                    <td>

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

@endsection
