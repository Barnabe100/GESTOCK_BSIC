@extends('includes.master')

@section('title', 'Créer utilisateur')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Créer un utilisateur</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('users.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Prénom</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                    </div>



                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label>Agence</label>
                            <select name="agence_id" class="form-control select2">
                                <option value=""></option>
                                @foreach ($agences as $agence)
                                    <option value="{{ $agence->id }}">{{ $agence->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Département</label>
                            <select name="departement_id" class="form-control select2">
                                <option value=""></option>
                                @foreach ($departements as $dep)
                                    <option value="{{ $dep->id }}">{{ $dep->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Service</label>
                            <select name="service_id" class="form-control select2">
                                <option value=""></option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="form-check mt-3">

                        <input class="form-check-input" type="checkbox" name="superieur" value="1"
                            {{ old('superieur', $user->superieur ?? false) ? 'checked' : '' }}>

                        <label class="form-check-label">

                            Directeur / Supérieur hiérarchique

                        </label>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Retour</a>

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
