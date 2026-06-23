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

                                <div class="col-md-6 mb-3">
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

                                <div class="col-md-6 mb-3">
                                    <label>Prix initial de l'article</label>
                                    <input type="number" id="prix_unitaire" name="prix_unitaire" class="form-control" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label>Quantité</label>
                                    <input type="number" name="quantite" class="form-control" required>
                                </div>



                                <div class="col-md-4 mb-3">
                                    <label>Prix unitaire de la commande</label>
                                    <input type="number" name="prix_unitaire" class="form-control" required>
                                </div>

                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    @if (
                                        $commande->user_id == auth()->id() ||
                                            auth()->user()->hasAnyRole(['SuperAdmin', 'Gestionnaire']))
                                        <button class="btn btn-success w-100">
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

                                    {{ number_format($ligne->prix_unitaire, 2, ',', ' ') }}

                                </td>

                                {{-- <td>
                                    {{ $ligne->quantite * $ligne->prix_unitaire }}
                                </td> --}}

                                <td>

                                    {{ number_format($ligne->quantite * $ligne->prix_unitaire, 2, ',', ' ') }}

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

                                                    </div>

                                                    <form action="{{ route('article-commandes.update', $ligne->id) }}"
                                                        method="POST">

                                                        @csrf
                                                        @method('PUT')

                                                        <div class="modal-body">

                                                            <div class="mb-3">

                                                                <label>

                                                                    Quantité

                                                                </label>

                                                                <input type="number" name="quantite" class="form-control"
                                                                    value="{{ $ligne->quantite }}" required>

                                                            </div>

                                                            <div class="mb-3">

                                                                <label>

                                                                    Prix unitaire

                                                                </label>

                                                                <input type="number" step="0.01" name="prix_unitaire"
                                                                    class="form-control" value="{{ $ligne->prix_unitaire }}"
                                                                    required>

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

    <script>
        $(document).ready(function() {

            $('#article_id').on('change', function() {

                let prix = $(this)
                    .find(':selected')
                    .data('prix');

                $('#prix_unitaire').val(
                    prix ?? ''
                );

            });

        });
    </script>
@endpush
