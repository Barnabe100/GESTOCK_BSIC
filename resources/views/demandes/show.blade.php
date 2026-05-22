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
                            {{ $demande->user->name }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Statut</label>
                        <div class="form-control bg-light">

                            @if ($demande->validation)
                                <span class="badge bg-success">Validée</span>
                            @else
                                <span class="badge bg-warning">En attente</span>
                            @endif

                            @if ($demande->livraison)
                                <span class="badge bg-primary">Livrée</span>
                            @endif

                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12 mb-3">
                        <label>Description</label>
                        <div class="form-control bg-light">
                            {{ $demande->description }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- 🔥 AJOUT ARTICLE -->
        <div class="card mt-4 shadow-sm">

            <div class="card-header bg-secondary text-white">
                Ajouter un article
            </div>


            <div class="card-body">

                @if (!$demande->validation)

                    <form method="POST" action="{{ route('article_demande.store') }}">
                        @csrf

                        <input type="hidden" name="demande_id" value="{{ $demande->id }}">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label>Article</label>
                                <select name="article_id" class="form-control">

                                    @foreach ($articles as $article)
                                        <option value="{{ $article->id }}">
                                            {{ $article->libelle }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Quantité</label>
                                <input type="number" name="quantite" class="form-control" required>
                            </div>

                        </div>
                        @if ($demande->user_id == auth()->id())
                            <button class="btn btn-success">
                                Ajouter
                            </button>
                        @endif
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
                            <th>Quantité</th>
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

                                    @if (!$demande->validation)
                                        <form action="{{ route('article_demande.destroy', $ligne->id) }}" method="POST"
                                            style="display:inline-block">

                                            @csrf
                                            @method('DELETE')

                                            {{-- <button class="btn btn-danger btn-sm"
                                                onclick="return confirm('Supprimer cet article ?')">
                                                <i class="fas fa-trash"></i>
                                            </button> --}}
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
