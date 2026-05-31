@extends('includes.master')

@section('title', 'États')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">

                <h5>

                    Gestion des états

                </h5>

            </div>

            <div class="card-body">

                <div class="row">

                    @include('etats.demandes.menu')

                    @include('etats.articles.menu')

                    @include('etats.stocks-periode.menu')

                </div>

            </div>

        </div>

    </div>


    @include('etats.modals.stock-periode')

    @include('etats.modals.stock-valorise')

    @include('etats.modals.entrees-stock')

    @include('etats.modals.sorties-stock')

    @include('etats.scripts.etats-stock')

    @include('etats.modals.stock-final')

@endsection
