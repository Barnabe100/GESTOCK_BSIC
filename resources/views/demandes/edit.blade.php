@extends('includes.master')

@section('title', 'Modifier demande')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Modifier demande</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('demandes.update', $demande->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ old('date', $demande->date) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Objet</label>
                            <input type="text" name="objet" class="form-control"
                                value="{{ old('objet', $demande->objet) }}">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control">{{ old('description', $demande->description) }}</textarea>
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>
                    <a href="{{ route('mes.demandes') }}" class="btn btn-secondary">Retour</a>

                </form>

            </div>

        </div>

    </div>

@endsection
