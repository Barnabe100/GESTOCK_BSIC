@extends('includes.master')

@section('title', 'Liste des utilisateurs')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0">Liste des utilisateurs</h5>

                <div class="d-flex gap-2">
                    <!-- EXCEL -->
                    <a href="{{ route('users.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>

                    <!-- CSV -->
                    <a href="{{ route('users.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>

                    <a href="{{ route('users.create') }}" class="btn btn-light btn-sm">
                        + Ajouter
                    </a>
                </div>
            </div>

            <!-- BODY -->
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">

                        <thead class="table-vert-brillant">
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Agence</th>
                                <th>Département</th>
                                <th>Service</th>
                                <th>Rôles</th>
                                <th width="180px">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($users as $key => $user)
                                <tr>

                                    <td>{{ $key + 1 }}</td>

                                    <td>{{ $user->name }}</td>

                                    <td>{{ $user->email }}</td>

                                    <td>{{ $user->agence->nom ?? '-' }}</td>

                                    <td>{{ $user->departement->nom ?? '-' }}</td>

                                    <td>{{ $user->service->nom ?? '-' }}</td>

                                    <td>
                                        @foreach ($user->roles as $role)
                                            <span class="badge bg-primary">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </td>

                                    <td>

                                        <a href="{{ route('users.show', $user->id) }}" title="Détail"
                                            class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('users.edit', $user->id) }}" title="Editer"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
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
