@extends('includes.master')

@section('title', 'Modifier utilisateur')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Modifier utilisateur</h5>
            </div>

            @error('superieur')
                <div class="alert alert-danger">
                    {{ $message }}
                </div>
            @enderror

            <div class="card-body">

                <form method="POST" action="{{ route('users.update', $user->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="name" class="form-control" value="{{ $user->name }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Prénom</label>
                            <input type="text" name="prenom" class="form-control" value="{{ $user->prenom }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $user->email }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Mot de passe (optionnel)</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                    </div>

                    <div class="row">



                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label>Agence</label>
                            <select id="agence_id" name="agence_id" class="form-control select2">
                                @foreach ($agences as $agence)
                                    <option value="{{ $agence->id }}"
                                        {{ $user->agence_id == $agence->id ? 'selected' : '' }}>
                                        {{ $agence->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Département</label>
                            <select id="departement_id" name="departement_id" class="form-control select2">
                                @foreach ($departements as $dep)
                                    <option value="{{ $dep->id }}"
                                        {{ $user->departement_id == $dep->id ? 'selected' : '' }}>
                                        {{ $dep->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Service</label>
                            <select id="service_id" name="service_id" class="form-control select2">
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}"
                                        {{ $user->service_id == $service->id ? 'selected' : '' }}>
                                        {{ $service->nom }}
                                    </option>
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

                    <!-- 🔥 MULTI ROLES -->
                    <div class="mb-3">
                        <label>Rôles</label>

                        @foreach ($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}"
                                    {{ $user->hasRole($role->name) ? 'checked' : '' }}>

                                <label class="form-check-label">
                                    {{ $role->name }}
                                </label>
                            </div>
                        @endforeach

                    </div>



                    <button class="btn btn-success">Enregistrer</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Retour</a>


                </form>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/users.js') }}"></script>
@endpush
