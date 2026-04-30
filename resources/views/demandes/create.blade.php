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
                        <input type="date" name="date" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Objet</label>
                        <input type="text" name="objet" class="form-control">
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                </div>

                <button class="btn btn-success">Enregistrer</button>

            </form>

        </div>

    </div>

</div>

@endsection