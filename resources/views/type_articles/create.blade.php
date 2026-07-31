@extends('includes.master')

@section('title', 'Nouveau type d\'article')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Créer un type d'article</h5>
            </div>

            <!-- BODY -->
            <div class="card-body">

                <!-- MESSAGE SUCCESS -->
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

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
                <form method="POST" action="{{ route('type-articles.store') }}">

                    @csrf

                    <!-- CODE -->
                    <div class="mb-3">
                        <label class="form-label">
                            Code du type d'article
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

                    <!-- LIBELLE -->
                    <div class="mb-3">
                        <label class="form-label">
                            Libellé du type d'article
                        </label>
                        <span class="text-danger">*</span>

                        <input type="text" name="libelle" class="form-control @error('libelle') is-invalid @enderror"
                            placeholder="Ex : Stylos" value="{{ old('libelle') }}" required>

                        @error('libelle')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-3">
                        <label class="form-label">
                            Description
                        </label>

                        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                            placeholder="Description du type d'article">{{ old('description') }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- BOUTONS -->
                    <div class="d-flex gap-2">

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
