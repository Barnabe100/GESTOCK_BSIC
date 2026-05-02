@extends('includes.master')

@section('title', 'Modifier agence')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Modifier l'agence</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('agences.update', $agence->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $agence->code) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control" value="{{ old('nom', $agence->nom) }}">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Lieu</label>
                            <input type="text" name="lieu" class="form-control"
                                value="{{ old('lieu', $agence->lieu) }}">
                        </div>

                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('agences.index') }}" class="btn btn-secondary">← Retour</a>
                        <button class="btn btn-success">Enregistrer</button>
                    </div>

                </form>

            </div>
        </div>
    </div>

@endsection
