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
                        <label>Prix</label>
                        <div class="form-control bg-light">{{ $article->prix_unitaire }}</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Catégorie</label>
                        <div class="form-control bg-light">{{ $article->categorie->libelle }}</div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Stock disponible</label>
                        <div class="form-control bg-light">{{ $article->stock_disponible }}</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Stock sécurité</label>
                        <div class="form-control bg-light">{{ $article->stock_securite }}</div>
                    </div>

                </div>

            </div>
        </div>

    </div>

@endsection
