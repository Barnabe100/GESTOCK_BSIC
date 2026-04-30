@extends('includes.master')

@section('title', 'Liste des départements')

@section('content')

<div class="container-fluid mt-4">

    <div class="card shadow-sm">

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
            <h5 class="mb-0">Liste des départements</h5>

            <a href="{{ route('departements.create') }}" class="btn btn-light btn-sm">
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
                            <th width="180px">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($departements as $key => $dep)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ $dep->code }}</td>
                            <td>{{ $dep->nom }}</td>

                            <td>
                                <a href="{{ route('departements.show',$dep->id) }}" class="btn btn-info btn-sm" title="Détail">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <a href="{{ route('departements.edit',$dep->id) }}" class="btn btn-warning btn-sm" title="Editer">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form action="{{ route('departements.destroy',$dep->id) }}" method="POST" style="display:inline-block">
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