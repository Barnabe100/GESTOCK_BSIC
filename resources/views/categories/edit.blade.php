@extends('includes.master')

@section('title', 'Modifier catégorie')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Modifier la catégorie</h5>
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
                <form method="POST" action="{{ route('categories.update', $categorie->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- LIGNE 1 -->
                    <div class="row">

                        <!-- CODE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $categorie->code) }}" required>

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
                                value="{{ old('libelle', $categorie->libelle) }}" required>

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
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $categorie->description) }}</textarea>

                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                    </div>

                    <!-- BOUTONS -->
                    <div class="d-flex justify-content-between">

                        <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                            ← Retour
                        </a>

                        <button type="submit" class="btn btn-success">
                            Enregistrer
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </div>

@endsection
