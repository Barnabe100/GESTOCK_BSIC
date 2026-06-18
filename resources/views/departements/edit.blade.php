@extends('includes.master')

@section('title', 'Modifier département')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Modifier le département</h5>
            </div>

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('departements.update', $departement->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $departement->code) }}">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
                                value="{{ old('nom', $departement->nom) }}">
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Agence</label>
                            <select name="agence_id" class="form-control select2">
                                @foreach ($agences as $ag)
                                    <option value="{{ $ag->id }}"
                                        {{ $ag->id == $departement->agence_id ? 'selected' : '' }}>
                                        {{ $ag->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-success">Enregistrer</button>
                        <a href="{{ route('departements.index') }}" class="btn btn-secondary">Retour</a>

                    </div>

                </form>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/select.js') }}"></script>
@endpush
