@extends('includes.master')

@section('title', 'Nouveau département')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Créer un département</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('departements.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code') }}">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
                                value="{{ old('nom') }}">
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Agence</label>
                            <select name="agence_id" class="form-control select2">
                                <option value=""></option>
                                @foreach ($agences as $ag)
                                    <option value="{{ $ag->id }}">{{ $ag->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>
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
