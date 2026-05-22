@extends('includes.master')

@section('title', 'Liste des articles')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

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
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Statut stock</th>
                                <th>Catégorie</th>
                                <th width="180px">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($articles as $key => $article)
                                <tr>

                                    <td>{{ $key + 1 }}</td>

                                    <td>{{ $article->code }}</td>

                                    <td>{{ $article->libelle }}</td>

                                    <td>{{ $article->prix_unitaire }}</td>

                                    <!-- STOCK AVEC COULEUR -->
                                    <td>
                                        @if ($article->stock_disponible <= $article->stock_securite)
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
                                        @if ($article->stock_disponible <= $article->stock_securite)
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Critique
                                            </span>
                                        @elseif($article->stock_disponible <= $article->stock_securite + 5)
                                            <span class="badge bg-warning text-dark">
                                                ⚠ Faible
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> OK
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $article->categorie->libelle ?? '' }}
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
