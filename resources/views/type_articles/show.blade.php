@extends('includes.master')

@section('title', 'Détail type d\'article')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Détail du type d'article
                </h5>

                <a href="{{ route('type-articles.index') }}" class="btn btn-light btn-sm">

                    Retour

                </a>

            </div>

            <!-- BODY -->
            <div class="card-body">

                <div class="row">

                    <!-- CODE -->
                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">Code</label>

                        <div class="form-control bg-light">
                            {{ $typeArticle->code }}
                        </div>

                    </div>

                    <!-- LIBELLE -->
                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">Libellé</label>

                        <div class="form-control bg-light">
                            {{ $typeArticle->libelle }}
                        </div>

                    </div>

                </div>

                <div class="row">

                    <!-- DESCRIPTION -->
                    <div class="col-md-6 mb-3">

                        <label class="fw-bold">
                            Description
                        </label>

                        <div class="form-control bg-light" style="min-height:80px;">

                            {{ $typeArticle->description ?? 'Aucune description' }}

                        </div>

                    </div>

                </div>

                <!-- ACTIONS -->
                <div class="d-flex justify-content-between mt-3">

                    <a href="{{ route('type-articles.index') }}" class="btn btn-secondary">

                        Retour

                    </a>

                    @can('gerer-type-article')
                        <a href="{{ route('type-articles.edit', $typeArticle->id) }}" class="btn btn-warning">

                            Modifier

                        </a>
                    @endcan

                </div>

            </div>

        </div>

    </div>

@endsection
