@extends('includes.master')

@section('title', 'Nouvelle catégorie')

@section('content')

<div class="container-fluid mt-4">

    <div class="card shadow-sm">

        <!-- HEADER -->
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Créer une catégorie</h5>
        </div>

        <!-- BODY -->
        <div class="card-body">

            <!-- MESSAGE SUCCESS -->
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- ERREURS -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- FORMULAIRE -->
            <form method="POST" action="{{ route('categories.store') }}">

                @csrf

                <!-- Code -->
                <div class="mb-3">
                    <label class="form-label">Code de la catégorie</label>
                    <input type="text"
                           name="code"
                           class="form-control @error('libelle') is-invalid @enderror"                          
                           value="{{ old('code') }}"
                           required>

                    @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- libelle -->
                <div class="mb-3">
                    <label class="form-label">libelle de la catégorie</label>
                    <input type="text"
                           name="libelle"
                           class="form-control @error('libelle') is-invalid @enderror"
                           placeholder="Ex: Informatique"
                           value="{{ old('libelle') }}"
                           required>

                    @error('libelle')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- DESCRIPTION -->
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description"
                              class="form-control @error('description') is-invalid @enderror"
                              rows="3"
                              placeholder="Description de la catégorie">{{ old('description') }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
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