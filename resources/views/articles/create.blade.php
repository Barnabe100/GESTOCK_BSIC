@extends('includes.master')

@section('title', 'Nouvel article')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Créer un article</h5>
            </div>

            <!-- BODY -->
            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('articles.store') }}">

                    @csrf

                    <!-- LIGNE 1 -->
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Code
                            </label>
                            <span class="text-danger">*</span>

                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code') }}" required>

                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Libellé
                            </label>
                            <span class="text-danger">*</span>

                            <input type="text" name="libelle" class="form-control @error('libelle') is-invalid @enderror"
                                value="{{ old('libelle') }}" required>

                            @error('libelle')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- LIGNE 2 -->

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Catégorie
                            </label>
                            <span class="text-danger">*</span>

                            <select name="categorie_id"
                                class="form-control select2 @error('categorie_id') is-invalid @enderror" required>

                                <option value=""></option>

                                @foreach ($categories as $categorie)
                                    <option value="{{ $categorie->id }}"
                                        {{ old('categorie_id') == $categorie->id ? 'selected' : '' }}>

                                        {{ $categorie->libelle }}

                                    </option>
                                @endforeach

                            </select>

                            @error('categorie_id')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Type d'article
                            </label>
                            <span class="text-danger">*</span>

                            <select name="type_article_id"
                                class="form-control select2 @error('type_article_id') is-invalid @enderror" required>

                                <option value=""></option>

                                @foreach ($typeArticles as $typeArticle)
                                    <option value="{{ $typeArticle->id }}"
                                        {{ old('type_article_id') == $typeArticle->id ? 'selected' : '' }}>

                                        {{ $typeArticle->libelle }}

                                    </option>
                                @endforeach

                            </select>

                            @error('type_article_id')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <hr>

                    <div class="alert alert-info">

                        <i class="fas fa-info-circle"></i>

                        Les informations relatives au prix et au stock seront renseignées
                        après la création de l'article via le bouton
                        <strong>Poursuivre ...</strong>.

                    </div>

                    <!-- BOUTONS -->

                    <div class="d-flex gap-2">

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
@endpush
