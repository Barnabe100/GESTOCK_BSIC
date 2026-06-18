@extends('includes.master')

@section('title', 'Nouveau service')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Créer un service</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('services.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="nom" class="form-control" value="{{ old('nom') }}" required>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Localisation</label>
                            <input type="text" name="localisation" class="form-control"
                                value="{{ old('localisation') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Département</label>
                            <span class="text-danger">*</span>
                            <select name="departement_id" class="form-control select2" required>
                                <option value=""></option>
                                @foreach ($departements as $dep)
                                    <option value="{{ $dep->id }}">{{ $dep->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>
                    <a href="{{ route('services.index') }}" class="btn btn-secondary">Retour</a>

                </form>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $('.select2').select2({
                placeholder: 'Sélectionner ...',
                allowClear: true,
                width: '100%'
            });

        });
    </script>
@endpush
