@extends('includes.master')

@section('title', 'Liste des commandes')

@section('content')

<div class="container-fluid mt-4">

    <div class="card shadow-sm">

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
            <h5 class="mb-0">Liste des commandes</h5>

            <a href="{{ route('commandes.create') }}" class="btn btn-light btn-sm">
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
                            <th>Numéro BC</th>
                            <th>Fournisseur</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Validation</th>
                            <th>Reception</th>
                            <th width="220px">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($commandes as $key => $commande)
                            <tr>

                                <td>{{ $key + 1 }}</td>

                                <td>{{ $commande->numero_bon_commande }}</td>

                                <td>{{ $commande->fournisseur->nom ?? '' }}</td>

                                <td>{{ $commande->montant }}</td>

                                <td>{{ $commande->date }}</td>

                                <!-- VALIDATION -->
                                <td>
                                    @if($commande->validation)
                                        <span class="badge bg-success">Validée</span>
                                    @else
                                        <span class="badge bg-warning">En attente</span>
                                    @endif
                                </td>

                                <!-- LIVRAISON -->
                                <td>
                                    @if($commande->livraison)
                                        <span class="badge bg-primary">Livrée</span>
                                    @else
                                        <span class="badge bg-danger">Non livrée</span>
                                    @endif
                                </td>

                                <td>

                                    <!-- VOIR -->
                                    <a href="{{ route('commandes.show', $commande->id) }}" title="Détail"
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <!-- EDIT -->
                                    @if(!$commande->validation)
                                        <a href="{{ route('commandes.edit', $commande->id) }}" title="Editer"
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @endif

                                    <!-- SUPPRIMER -->
                                    @if(!$commande->validation)
                                        <form action="{{ route('commandes.destroy', $commande->id) }}"
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
                                    @endif

                                    <!-- VALIDER -->
                                    @if(!$commande->validation)
                                        <form action="{{ route('commandes.valider', $commande->id) }}"
                                              method="POST"
                                              style="display:inline-block">
                                            @csrf
                                            <button type="button" title="Valider"
                                                class="btn btn-success btn-sm"
                                                onclick="confirmAction(this.form, 'Confirmer la validation de cette commande ?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        </form>
                                    @endif

                                    <!-- LIVRER -->
                                    @if($commande->validation && !$commande->livraison)
                                        <form action="{{ route('commandes.livrer', $commande->id) }}"
                                              method="POST"
                                              style="display:inline-block">
                                            @csrf
                                            <button type="button" title="Recevoir"
                                                class="btn btn-success btn-sm"
                                                onclick="confirmAction(this.form, 'Confirmer la reception de cette commande ?')">
                                                <i class="fas fa-box-open"></i>
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