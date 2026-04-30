@extends('includes.master')

@section('title', 'Liste des fournisseurs')

@section('content')

<div class="container-fluid mt-4">

    <div class="card shadow-sm">

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
            <h5 class="mb-0">Liste des fournisseurs</h5>

            <a href="{{ route('fournisseurs.create') }}" class="btn btn-light btn-sm">
                + Ajouter
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable">

                    <thead class="table-vert-brillant">
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th width="180px">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($fournisseurs as $key => $fournisseur)
                            <tr>

                                <td>{{ $key + 1 }}</td>

                                <td>{{ $fournisseur->code }}</td>

                                <td>{{ $fournisseur->nom }}</td>

                                <td>{{ $fournisseur->telephone }}</td>

                                <td>{{ $fournisseur->email }}</td>

                                <td>{{ $fournisseur->adresse }}</td>

                                <td>

                                    <!-- VOIR -->
                                    <a href="{{ route('fournisseurs.show', $fournisseur->id) }}" title="Détail"
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <!-- MODIFIER -->
                                    <a href="{{ route('fournisseurs.edit', $fournisseur->id) }}" title="Editer"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- SUPPRIMER -->
                                    <form action="{{ route('fournisseurs.destroy', $fournisseur->id) }}"
                                          method="POST"
                                          style="display:inline-block">

                                        @csrf
                                        @method('DELETE')

                                        <button type="button" title="Supprimer"
                                                class="btn btn-danger btn-sm"
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