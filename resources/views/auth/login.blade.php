@extends('layouts.auth')

@section('title', 'Connexion')

@section('content')

    <h3 class="text-center mb-4">

        Connexion

    </h3>

    @if ($errors->any())
        <div class="alert alert-danger">

            {{ $errors->first() }}

        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">

        @csrf

        <!-- Email -->
        <div class="mb-3">

            <label>Email</label>

            <input type="email" name="email" class="form-control" required>

        </div>

        <!-- Password -->
        <div class="mb-3">

            <label>Mot de passe</label>

            <div class="input-group">

                <input type="password" name="password" id="password" class="form-control" required>

                <!-- BOUTON -->
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">

                    <i class="fas fa-eye" id="eyeIcon"></i>


                </button>

            </div>

        </div>

        <button class="btn btn-primary w-100 mt-5">

            Se connecter

        </button>

    </form>

@endsection
