@extends('includes.master')

@section('title', 'Modifier service')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Modifier le service</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('services.update', $service->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control"
                                value="{{ old('code', $service->code) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control"
                                value="{{ old('nom', $service->nom) }}">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Localisation</label>
                            <input type="text" name="localisation" class="form-control"
                                value="{{ old('localisation', $service->localisation) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Département</label>
                            <select name="departement_id" class="form-control">
                                @foreach ($departements as $dep)
                                    <option value="{{ $dep->id }}"
                                        {{ $dep->id == $service->departement_id ? 'selected' : '' }}>
                                        {{ $dep->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-success">Enregistrer</button>
                        <a href="{{ route('services.index') }}" class="btn btn-secondary">Retour</a>
                    </div>

                </form>

            </div>
        </div>
    </div>

@endsection
