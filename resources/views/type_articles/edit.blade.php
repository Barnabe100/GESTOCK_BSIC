@extends('includes.master')

@section('title', 'Modifier le type d\'article')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Modifier le type d'article</h5>
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
                <form method="POST" action="{{ route('type-articles.update', $typeArticle->id) }}">

                    @csrf
                    @method('PUT')

                    <!-- LIGNE 1 -->
                    <div class="row">

                        <!-- CODE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code</label>

                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $typeArticle->code) }}" required>

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
                                value="{{ old('libelle', $typeArticle->libelle) }}" required>

                            @error('libelle')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                    </div>

                    <!-- LIGNE 2 -->
                    <div class="row">

                        <!-- DESCRIPTION -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">Description</label>

                            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $typeArticle->description) }}</textarea>

                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- BOUTONS -->
                    <div style="display: flex; gap: 10px;">

                        <button type="submit" class="btn btn-success">
                            Enregistrer
                        </button>

                        <a href="{{ route('type-articles.index') }}" class="btn btn-secondary">
                            Retour
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection
