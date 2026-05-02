@extends('includes.master')

@section('title', 'Liste des catégories')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0">Liste des catégories</h5>

                <div class="d-flex gap-2">
                    <!-- EXCEL -->
                    <a href="{{ route('categories.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>

                    <!-- CSV -->
                    <a href="{{ route('categories.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>

                    <a href="{{ route('categories.create') }}" class="btn btn-light btn-sm">
                        + Ajouter
                    </a>
                </div>

            </div>

            <!-- BODY -->
            <div class="card-body">



                <!-- TABLE -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">

                        <thead class="table-vert-brillant">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Libellé</th>
                                <th>Description</th>
                                <th width="180px">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($categories as $key => $categorie)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $categorie->code }}</td>
                                    <td>{{ $categorie->libelle }}</td>
                                    <td>{{ $categorie->description }}</td>

                                    <td>

                                        <a href="{{ route('categories.show', $categorie->id) }}" title="Détail"
                                            class="btn btn-info btn-sm">

                                            <i class="fas fa-eye"></i>
                                        </a>


                                        <a href="{{ route('categories.edit', $categorie->id) }}" title="Editer"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="{{ route('categories.destroy', $categorie->id) }}" method="POST"
                                            style="display:inline-block">

                                            @csrf
                                            @method('DELETE')

                                            {{-- <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Supprimer ?')">
                                                    <i class="fas fa-trash"></i>
                                            </button> --}}

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
