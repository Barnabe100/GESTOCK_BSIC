@extends('includes.master')

@section('title', 'Détail utilisateur')

@section('content')

    <div class="container-fluid mt-4">

        {{-- @if (auth()->id() == $user->id && $user->force_password_change)
            <div class="alert alert-warning">

                <i class="fas fa-key"></i>

                Pour des raisons de sécurité,
                veuillez modifier votre mot de passe
                avant de continuer à utiliser l'application.

            </div>
        @endif --}}

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5>Détail utilisateur</h5>

                <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
                    ← Retour
                </a>
            </div>

            <!-- BODY -->
            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Nom</label>
                        <div class="form-control bg-light">
                            {{ $user->name }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Prénom</label>
                        <div class="form-control bg-light">
                            {{ $user->prenom }}
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Email</label>
                        <div class="form-control bg-light">
                            {{ $user->email }}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>Agence</label>
                        <div class="form-control bg-light">
                            {{ $user->agence->nom ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Département</label>
                        <div class="form-control bg-light">
                            {{ $user->departement->nom ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Service</label>
                        <div class="form-control bg-light">
                            {{ $user->service->nom ?? '-' }}
                        </div>
                    </div>

                </div>



                <!-- ROLES -->

                @if (auth()->check() &&
                        auth()->user()->hasAnyRole(['Admin', 'SuperAdmin']))
                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <label>Rôles</label>

                            <div class="form-control bg-light">

                                @forelse($user->roles as $role)
                                    <span class="badge bg-primary">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    Aucun rôle
                                @endforelse

                            </div>
                        </div>

                    </div>
                @endif

            </div>

        </div>

        @if (auth()->id() == $user->id)
            <div class="mt-3">

                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning">

                    <i class="fas fa-key"></i>

                    Modifier mon mot de passe

                </a>

            </div>
        @endif

    </div>

@endsection
