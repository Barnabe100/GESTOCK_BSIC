@extends('includes.master')

@section('title', 'Modifier article')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Modifier l'article</h5>
            </div>

            <!-- BODY -->
            <div class="card-body">

                <!-- ERREURS -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- FORMULAIRE -->
                <form method="POST" action="{{ route('articles.update', $article->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- LIGNE 1 -->
                    <div class="row">

                        <!-- CODE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $article->code) }}" required>

                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- LIBELLE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Libellé</label>
                            <input type="text" name="libelle" class="form-control @error('libelle') is-invalid @enderror"
                                value="{{ old('libelle', $article->libelle) }}" required>

                            @error('libelle')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                    </div>

                    <!-- LIGNE 2 -->
                    <div class="row">

                        <!-- PRIX -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Prix unitaire</label>
                            <input type="number" name="prix_unitaire"
                                class="form-control @error('prix_unitaire') is-invalid @enderror"
                                value="{{ old('prix_unitaire', $article->prix_unitaire) }}" required>

                            @error('prix_unitaire')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- CATEGORIE -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie_id" class="form-control @error('categorie_id') is-invalid @enderror">

                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ $cat->id == $article->categorie_id ? 'selected' : '' }}>
                                        {{ $cat->libelle }}
                                    </option>
                                @endforeach

                            </select>

                            @error('categorie_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type article</label>
                            <select name="type_article_id"
                                class="form-control @error('type_article_id') is-invalid @enderror">

                                @foreach ($typeArticles as $typeart)
                                    <option value="{{ $typeart->id }}"
                                        {{ $typeart->id == $article->type_article_id ? 'selected' : '' }}>
                                        {{ $typeart->libelle }}
                                    </option>
                                @endforeach

                            </select>

                            @error('type_article_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                    </div>

                    <!-- LIGNE 3 -->
                    <div class="row">

                        <!-- STOCK -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock disponible</label>
                            <input type="number" name="stock_disponible" class="form-control"
                                value="{{ old('stock_disponible', $article->stock_disponible) }}">
                        </div>

                        <!-- STOCK SECURITE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock sécurité</label>
                            <input type="number" name="stock_securite" class="form-control"
                                value="{{ old('stock_securite', $article->stock_securite) }}">
                        </div>

                    </div>

                    <!-- BOUTONS -->
                    <div style="display: flex; gap: 10px;">



                        <button type="submit" class="btn btn-success">
                            Enregistrer
                        </button>

                        <a href="{{ route('articles.index') }}" class="btn btn-secondary">
                            Retour
                        </a>
                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection
