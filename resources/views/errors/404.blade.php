@extends('includes.master')

@section('title', 'Page introuvable')

@section('content')

    <div class="container text-center mt-5">

        <h1 class="display-1 text-primary">

            404

        </h1>

        <h3>

            Page introuvable

        </h3>

        <p class="text-muted">

            La page demandée n'existe pas.

        </p>

        <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">

            Retour au dashboard

        </a>

    </div>

@endsection
