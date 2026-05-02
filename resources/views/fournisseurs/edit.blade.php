@extends('includes.master')

@section('title', 'Modifier fournisseur')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Modifier fournisseur</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('fournisseurs.update', $fournisseur->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control"
                                value="{{ old('code', $fournisseur->code) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control"
                                value="{{ old('nom', $fournisseur->nom) }}">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" class="form-control"
                                value="{{ old('telephone', $fournisseur->telephone) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $fournisseur->email) }}">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Adresse</label>
                            <input type="text" name="adresse" class="form-control"
                                value="{{ old('adresse', $fournisseur->adresse) }}">
                        </div>

                    </div>

                    <div class="d-flex justify-content-between">

                        <a href="{{ route('fournisseurs.index') }}" class="btn btn-secondary">
                            ← Retour
                        </a>

                        <button class="btn btn-success">
                            Enregistrer
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection
