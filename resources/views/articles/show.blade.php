@extends('includes.master')

@section('title', 'Détail article')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5>Détail article</h5>

                <a href="{{ route('articles.index') }}" class="btn btn-light btn-sm"> Retour</a>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Code</label>
                        <div class="form-control bg-light">{{ $article->code }}</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Libellé</label>
                        <div class="form-control bg-light">{{ $article->libelle }}</div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Catégorie</label>
                        <div class="form-control bg-light">{{ $article->categorie->libelle }}</div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Prix unitaire</label>
                        <div class="form-control bg-light">

                            @if ($article->prix_unitaire === null)
                                <span class="badge bg-secondary">
                                    Non paramétré
                                </span>
                            @else
                                {{ number_format($article->prix_unitaire, 2, ',', ' ') }}
                            @endif

                            {{-- {{ !empty($article->prix_unitaire) ? number_format($article->prix_unitaire, 2, ',', ' ') : 'Prix non renseigné' }} --}}

                        </div>
                    </div>

                    <div class="col-md-3 mb-3">

                        <label>Coût Moyen Pondéré</label>

                        <div class="form-control bg-light">

                            {{ number_format($article->cmp, 2, ',', ' ') }}

                            FCFA

                        </div>

                    </div>



                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Stock disponible</label>
                        <div class="form-control bg-light">
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
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Stock sécurité</label>
                        <div class="form-control bg-light">
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
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>

@endsection
