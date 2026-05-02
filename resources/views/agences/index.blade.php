@extends('includes.master')

@section('title', 'Liste des agences')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5>Liste des agences</h5>

                <div class="d-flex gap-2">

                    <!-- EXCEL -->
                    <a href="{{ route('agences.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>

                    <!-- CSV -->
                    <a href="{{ route('agences.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>

                    <a href="{{ route('agences.create') }}" class="btn btn-light btn-sm">
                        + Ajouter
                    </a>

                </div>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">

                        <thead class="table-vert-brillant">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Nom</th>
                                <th>Lieu</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($agences as $key => $a)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $a->code }}</td>
                                    <td>{{ $a->nom }}</td>
                                    <td>{{ $a->lieu }}</td>

                                    <td>
                                        <a href="{{ route('agences.show', $a->id) }}" class="btn btn-info btn-sm"
                                            title="Détail">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('agences.edit', $a->id) }}" class="btn btn-warning btn-sm"
                                            title="Editer">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="{{ route('agences.destroy', $a->id) }}" method="POST"
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
