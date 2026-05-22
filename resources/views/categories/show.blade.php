@extends('includes.master')

@section('title', 'Détail catégorie')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Détail de la catégorie</h5>

                <a href="{{ route('categories.index') }}" class="btn btn-light btn-sm">
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
                            {{ $categorie->code }}
                        </div>
                    </div>

                    <!-- LIBELLE -->
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Libellé</label>
                        <div class="form-control bg-light">
                            {{ $categorie->libelle }}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <!-- DESCRIPTION -->
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Description</label>
                        <div class="form-control bg-light" style="min-height: 80px;">
                            {{ $categorie->description ?? 'Aucune description' }}
                        </div>
                    </div>

                </div>

                <!-- ACTIONS -->
                <div class="d-flex justify-content-between mt-3">

                    <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                        Retour
                    </a>

                    @can('categorie-manage')
                        <a href="{{ route('categories.edit', $categorie->id) }}" class="btn btn-warning">
                            Modifier
                        </a>
                    @endcan

                </div>

            </div>

        </div>

    </div>

@endsection
