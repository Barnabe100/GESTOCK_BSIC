@extends('includes.master')

@section('title', 'Erreur serveur')

@section('content')

    <div class="container text-center mt-5">

        <h1 class="display-1 text-warning">

            500

        </h1>

        <h3>

            Erreur serveur

        </h3>

        <p class="text-muted">

            Une erreur interne est survenue.

        </p>

        <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">

            Retour au dashboard

        </a>

    </div>

@endsection
