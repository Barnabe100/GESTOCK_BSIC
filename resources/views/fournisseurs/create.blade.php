@extends('includes.master')

@section('title', 'Nouveau fournisseur')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Créer un fournisseur</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('fournisseurs.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control" value="{{ old('nom') }}">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Adresse</label>
                            <input type="text" name="adresse" class="form-control">
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>

                </form>

            </div>

        </div>

    </div>

@endsection
