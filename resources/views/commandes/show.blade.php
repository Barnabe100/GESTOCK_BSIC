@extends('includes.master')

@section('title', 'Détail commande')

@section('content')

    <div class="container-fluid mt-4">

        <!-- 🔹 INFOS COMMANDE -->
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5>Détail commande</h5>

                <a href="{{ route('commandes.index') }}" class="btn btn-light btn-sm">
                    ← Retour
                </a>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Numéro BC</label>
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

                    <div class="col-md-4 mb-3">
                        <label>Date</label>
                        <div class="form-control bg-light">
                            {{ $commande->date }}
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Montant</label>
                        <div class="form-control bg-light">
                            {{ $commande->montant }}
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Statut</label>
                        <div class="form-control bg-light">

                            @if ($commande->validation)
                                <span class="badge bg-success">Validée</span>
                            @else
                                <span class="badge bg-warning">En attente</span>
                            @endif

                            @if ($commande->livraison)
                                <span class="badge bg-primary">Livrée</span>
                            @endif

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

            </div>

        </div>

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
                                <select name="article_id" class="form-control">
                                    @foreach ($articles as $article)
                                        <option value="{{ $article->id }}">
                                            {{ $article->libelle }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Quantité</label>
                                <input type="number" name="quantite" class="form-control" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Prix</label>
                                <input type="number" name="prix" class="form-control" required>
                            </div>

                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                <button class="btn btn-success w-100">
                                    Ajouter
                                </button>
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

        <!-- 🔥 LISTE DES ARTICLES -->
        <div class="card mt-4 shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                Articles de la commande

                <div class="d-flex gap-2">
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
                </div>
            </div>

            <div class="card-body">

                <table class="table table-bordered table-striped">

                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Article</th>
                            <th>Quantité</th>
                            <th>Prix</th>
                            <th>Total</th>
                            <th width="120px">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($commande->articles as $key => $ligne)
                            <tr>

                                <td>{{ $key + 1 }}</td>

                                <td>{{ $ligne->article->libelle }}</td>

                                <td>{{ $ligne->quantite }}</td>

                                <td>{{ $ligne->prix }}</td>

                                <td>
                                    {{ $ligne->quantite * $ligne->prix }}
                                </td>

                                <td>

                                    @if (!$commande->validation)
                                        <form method="POST" action="{{ route('article_commande.destroy', $ligne->id) }}"
                                            style="display:inline-block">

                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-danger btn-sm"
                                                onclick="return confirm('Supprimer cet article ?')">
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
                                <td colspan="6" class="text-center">
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
