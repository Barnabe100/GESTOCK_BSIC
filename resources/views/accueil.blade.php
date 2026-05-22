@extends('includes.master')

@section('title', 'Accueil')

@section('content')

    <div class="container mt-5">

        <div class="text-center">

            <i class="fas fa-handshake fa-4x text-primary mb-3"></i>

            <h2>

                Bienvenue

                {{ Auth::user()->prenom }}
                {{ Auth::user()->name }}

            </h2>

            <p class="text-muted">

                Accédez rapidement à vos demandes.

            </p>

        </div>

        <div class="row mt-5 justify-content-center">

            <!-- MES DEMANDES -->

            <div class="col-md-4">

                <div class="card shadow text-center">

                    <div class="card-body">

                        <i class="fas fa-list fa-3x text-success mb-3"></i>

                        <h5>

                            Mes demandes

                        </h5>

                        <a href="{{ route('mes.demandes') }}" class="btn btn-success">

                            Ouvrir

                        </a>

                    </div>

                </div>

            </div>

            <!-- NOUVELLE DEMANDE -->

            <div class="col-md-4">

                <div class="card shadow text-center">

                    <div class="card-body">

                        <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>

                        <h5>

                            Nouvelle demande

                        </h5>

                        <a href="{{ route('demandes.create') }}" class="btn btn-primary">

                            Créer

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
