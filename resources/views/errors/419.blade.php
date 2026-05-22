@extends('includes.master')

@section('title', 'Session expirée')

@section('content')

    <div class="container text-center mt-5">

        <h1 class="display-1 text-secondary">

            419

        </h1>

        <h3>

            Session expirée

        </h3>

        <p class="text-muted">

            Veuillez vous reconnecter.

        </p>

        <a href="{{ route('login') }}" class="btn btn-primary mt-3">

            Se connecter

        </a>

    </div>

@endsection
