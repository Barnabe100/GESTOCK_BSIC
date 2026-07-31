@extends('includes.master')

@section('title', 'Nouvelle demande')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Créer une demande</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('demandes.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Date</label>
                            <span class="text-danger">*</span>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                            required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Objet</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="objet" class="form-control" required>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>
                    <a href="{{ route('mes.demandes') }}" class="btn btn-secondary">Retour</a>

                </form>

            </div>

        </div>

    </div>

@endsection
