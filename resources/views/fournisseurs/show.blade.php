@extends('includes.master')

@section('title', 'Détail fournisseur')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5>Détail fournisseur</h5>

                <a href="{{ route('fournisseurs.index') }}" class="btn btn-light btn-sm">
                    ← Retour
                </a>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Code</label>
                        <div class="form-control bg-light">
                            {{ $fournisseur->code }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Nom</label>
                        <div class="form-control bg-light">
                            {{ $fournisseur->nom }}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Téléphone</label>
                        <div class="form-control bg-light">
                            {{ $fournisseur->telephone }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <div class="form-control bg-light">
                            {{ $fournisseur->email }}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Adresse</label>
                        <div class="form-control bg-light">
                            {{ $fournisseur->adresse }}
                        </div>
                    </div>

                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('fournisseurs.index') }}" class="btn btn-secondary">
                        ← Retour
                    </a>

                    <a href="{{ route('fournisseurs.edit', $fournisseur->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>

            </div>

        </div>

    </div>

@endsection
