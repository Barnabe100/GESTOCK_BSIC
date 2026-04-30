@extends('includes.master')

@section('title', 'Liste des demandes')

@section('content')

<div class="container-fluid mt-4">

    <div class="card shadow-sm">

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
            <h5 class="mb-0">Liste des demandes</h5>

            <a href="{{ route('demandes.create') }}" class="btn btn-light btn-sm">
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
                            <th>Objet</th>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Validation</th>
                            <th>Livraison</th>
                            <th width="180px">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($demandes as $key => $demande)
                            <tr>

                                <td>{{ $key + 1 }}</td>

                                <td>{{ $demande->objet }}</td>

                                <td>{{ $demande->date }}</td>

                                <td>{{ $demande->user->name ?? '' }}</td>

                                <td>
                                    @if($demande->validation)
                                        <span class="badge bg-success">Validée</span>
                                    @else
                                        <span class="badge bg-warning">En attente</span>
                                    @endif
                                </td>

                                <td>
                                    @if($demande->livraison)
                                        <span class="badge bg-success">Livrée</span>
                                    @else
                                        <span class="badge bg-danger">Non livrée</span>
                                    @endif
                                </td>

                                <td>

                                    <a href="{{ route('demandes.show', $demande->id) }}" title="Détail"
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a href="{{ route('demandes.edit', $demande->id) }}" title="Editer"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('demandes.destroy', $demande->id) }}"
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

                                    @if(!$demande->validation)
                                    <form action="{{ route('demandes.valider', $demande->id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        {{-- <button class="btn btn-success btn-sm"
                                                onclick="return confirm('Confirmer la validation de cette demande ?')">
                                                <i class="fas fa-check-circle"></i>
                                        </button> --}}

                                        <button type="button" title="Valider"
                                                class="btn btn-success btn-sm"
                                                onclick="confirmAction(this.form, 'Confirmer la validation de cette demande ?')">
                                                
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if($demande->validation && !$demande->livraison)
                                    <form action="{{ route('demandes.livrer', $demande->id) }}" method="POST" style="display:inline-block">
                                        @csrf                                        

                                        <button type="button" title="Livrer"
                                                class="btn btn-success btn-sm"
                                                onclick="confirmAction(this.form, 'Confirmer la livraison de cette demande ?')">
                                                <i class="fas fa-truck"></i>
                                        </button>
                                    </form>
                                    @endif

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