@extends('layouts.auth')

@section('title', 'Connexion')

@section('content')

    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center" style="background-color: #9fd3fb;">

        <div class="col-md-4">

            <!-- LOGO -->
            <div class="text-center mb-3">

                <img src="{{ asset('assets/BSICBF-1.png') }}" alt="Logo" width="220">

            </div>

            <!-- TITRE -->
            <h2 class="text-center fw-bold text-primary mb-1">

                GESTION STOCK

            </h2>

            <p class="text-center text-dark mb-4">

                Plateforme de gestion des stocks et demandes

            </p>

            <!-- CONNEXION -->
            <h3 class="text-center mb-4">

                Connexion

            </h3>

            <form method="POST" action="{{ route('login') }}">

                @csrf

                <!-- Email -->
                <div class="mb-3">

                    <label>Email</label>

                    <input type="email" name="email" class="form-control" required>

                </div>

                <!-- Password -->
                <div class="mb-4">

                    <label>Mot de passe</label>

                    <input type="password" name="password" class="form-control" required>

                </div>

                <button class="btn btn-primary w-100">

                    Se connecter

                </button>

            </form>

        </div>

    </div>

@endsection
