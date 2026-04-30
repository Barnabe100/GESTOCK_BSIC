@extends('includes.master')

@section('title', 'Détail département')

@section('content')

<div class="container-fluid mt-4">
    <div class="card shadow-sm">

        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h5>Détail du département</h5>

            <a href="{{ route('departements.index') }}" class="btn btn-light btn-sm">
                ← Retour
            </a>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Code</label>
                    <div class="form-control bg-light">
                        {{ $departement->code }}
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Nom</label>
                    <div class="form-control bg-light">
                        {{ $departement->nom }}
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('departements.index') }}" class="btn btn-secondary">
                    ← Retour
                </a>

                <a href="{{ route('departements.edit', $departement->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                </a>
            </div>

        </div>
    </div>
</div>

@endsection