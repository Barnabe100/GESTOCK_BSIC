@extends('includes.master')

@section('title', 'Liste des services')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0">Liste des services</h5>

                <div class="d-flex gap-2">
                    <!-- EXCEL -->
                    <a href="{{ route('services.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>

                    <!-- CSV -->
                    <a href="{{ route('services.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>

                    @can('gerer-service')
                        <a href="{{ route('services.create') }}" class="btn btn-light btn-sm">
                            + Ajouter
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">

                        <thead style="background-color: #c3c3ee;">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Nom</th>
                                <th>Département</th>
                                <th width="180px">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($services as $key => $service)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $service->code }}</td>
                                    <td>{{ $service->nom }}</td>
                                    <td>{{ $service->departement->nom ?? '' }}</td>

                                    <td>
                                        <a href="{{ route('services.show', $service->id) }}" class="btn btn-info btn-sm"
                                            title="Détail">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('services.edit', $service->id) }}" class="btn btn-warning btn-sm"
                                            title="Editer">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="{{ route('services.destroy', $service->id) }}" method="POST"
                                            style="display:inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" title="Supprimer" class="btn btn-danger btn-sm"
                                                onclick="confirmDelete(this.form)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    </div>

@endsection
