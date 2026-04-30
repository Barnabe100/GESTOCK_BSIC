@extends('layouts.auth')

@section('title', 'Connexion')

@section('content')

<h3 class="text-center mb-4">Connexion</h3>

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
        <input type="password" name="password" class="form-control" required>
    </div>

    <button class="btn btn-primary w-100">
        Se connecter
    </button>

    <button href="{{ route('commandes.index') }}" class="btn btn-primary w-100">
        S'inscrire
    </button>

</form>

@endsection