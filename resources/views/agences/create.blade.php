@extends('includes.master')

@section('title', 'Nouvelle agence')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Créer une agence</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('agences.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Lieu</label>
                            <input type="text" name="lieu" class="form-control">
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>

                </form>

            </div>
        </div>
    </div>

@endsection
