@extends('includes.master')

@section('title', 'Détail service')

@section('content')

<div class="container-fluid mt-4">
    <div class="card shadow-sm">

        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h5>Détail du service</h5>

            <a href="{{ route('services.index') }}" class="btn btn-light btn-sm">
                ← Retour
            </a>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Code</label>
                    <div class="form-control bg-light">
                        {{ $service->code }}
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Nom</label>
                    <div class="form-control bg-light">
                        {{ $service->nom }}
                    </div>
                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Localisation</label>
                    <div class="form-control bg-light">
                        {{ $service->localisation }}
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Département</label>
                    <div class="form-control bg-light">
                        {{ $service->departement->nom ?? '' }}
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('services.index') }}" class="btn btn-secondary">
                    ← Retour
                </a>

                <a href="{{ route('services.edit', $service->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                </a>
            </div>

        </div>
    </div>
</div>

@endsection