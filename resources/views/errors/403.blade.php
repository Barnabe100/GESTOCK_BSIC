@extends('includes.master')

@section('title', 'Accès refusé')

@section('content')

    <div class="container text-center mt-5">

        <h1 class="display-1 text-danger">

            403

        </h1>

        <h3>

            Accès refusé

        </h3>

        <p class="text-muted">

            Vous n'avez pas l'autorisation d'accéder à cette page.

        </p>

        <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">

            Retour au dashboard

        </a>

    </div>

@endsection
