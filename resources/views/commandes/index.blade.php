@extends('includes.master')

@section('title', 'Liste des commandes')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0">Liste des commandes</h5>

                <div class="d-flex gap-2">
                    <!-- EXCEL -->
                    <a href="{{ route('commandes.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel
                    </a>

                    <!-- CSV -->
                    <a href="{{ route('commandes.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>

                    @hasanyrole('Gestionnaire|SuperAdmin')
                        <a href="{{ route('commandes.create') }}" class="btn btn-light btn-sm">
                            + Ajouter
                        </a>
                    @endhasanyrole
                </div>

            </div>

            <!-- BODY -->
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">

                        <thead style="background-color: #c3c3ee;">
                            <tr>
                                <th>#</th>
                                <th>Numéro BC</th>
                                <th>Fournisseur</th>
                                {{-- <th>Montant</th> --}}
                                <th>Date</th>
                                <th>Validation</th>
                                <th>Reception</th>
                                <th width="220px">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($commandes as $key => $commande)
                                <tr>

                                    <td>{{ $key + 1 }}</td>

                                    <td>{{ $commande->numero_bon_commande }}</td>

                                    <td>{{ $commande->fournisseur->nom ?? '' }}</td>

                                    {{-- <td>{{ $commande->montant }}</td> --}}

                                    <td>{{ $commande->date }}</td>

                                    <!-- VALIDATION -->
                                    <td>
                                        @if ($commande->validation)
                                            <span class="badge bg-success">Validée</span>
                                        @else
                                            <span class="badge bg-warning">En attente</span>
                                        @endif
                                    </td>

                                    <!-- LIVRAISON -->
                                    <td>

                                        @if ($commande->articles->sum('quantite_livree') == 0)
                                            <span class="badge bg-danger">

                                                En attente

                                            </span>
                                        @elseif($commande->articles->sum('quantite_restante') > 0)
                                            <span class="badge bg-warning">

                                                Partielle

                                            </span>
                                        @else
                                            <span class="badge bg-success">

                                                Receptionnée

                                            </span>
                                        @endif

                                    </td>

                                    <td>
                                        @if (
                                            $commande->user_id == auth()->id() ||
                                                auth()->user()->hasAnyRole(['SuperAdmin', 'Gestionnaire', 'ValidateurCommande']))
                                            <!-- VOIR -->
                                            <a href="{{ route('commandes.show', $commande->id) }}" title="Détail"
                                                class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                        @if (
                                            $commande->user_id == auth()->id() ||
                                                auth()->user()->hasAnyRole(['SuperAdmin', 'Gestionnaire']))
                                            <!-- EDIT -->
                                            @if (!$commande->validation)
                                                <a href="{{ route('commandes.edit', $commande->id) }}" title="Editer"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            @endif
                                        @endif
                                        @role('SuperAdmin')
                                            <!-- SUPPRIMER -->
                                            @if (!$commande->validation)
                                                <form action="{{ route('commandes.destroy', $commande->id) }}" method="POST"
                                                    style="display:inline-block">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button" title="Supprimer" class="btn btn-danger btn-sm"
                                                        onclick="confirmDelete(this.form)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>

                                                </form>
                                            @endif
                                        @endrole

                                        <!-- VALIDER -->
                                        @if (auth()->user()->hasAnyRole(['ValidateurCommande', 'SuperAdmin']))
                                            @if (!$commande->validation && $commande->statut == 'Prête pour validation')
                                                <form action="{{ route('commandes.valider', $commande->id) }}"
                                                    method="POST" style="display:inline-block">
                                                    @csrf
                                                    <button type="button" title="Valider" class="btn btn-success btn-sm"
                                                        onclick="confirmAction(this.form, 'Confirmer la validation de cette commande ?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif


                                            <!-- LIVRER -->

                                            {{-- @if ($commande->validation && !$commande->livraison) --}}
                                            @if ($commande->validation && $commande->articles->sum('quantite_restante') > 0)
                                                {{-- <form action="{{ route('commandes.livrer', $commande->id) }}"
                                                    method="POST" style="display:inline-block">
                                                    @csrf
                                                    <button type="button" title="Recevoir" class="btn btn-success btn-sm"
                                                        onclick="confirmAction(this.form, 'Confirmer la reception de cette commande ?')">
                                                        <i class="fas fa-box-open"></i>
                                                    </button>
                                                </form> --}}

                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#livrer{{ $commande->id }}">

                                                    <i class="fas fa-box-open"></i>

                                                </button>

                                                <div class="modal fade" id="livrer{{ $commande->id }}">

                                                    <div class="modal-dialog modal-xl">

                                                        <div class="modal-content">

                                                            <div class="modal-header bg-primary text-white">

                                                                <h5>

                                                                    Réception commande

                                                                    {{ $commande->numero_bon_commande }}

                                                                </h5>

                                                            </div>

                                                            <form action="{{ route('commandes.livrer', $commande->id) }}"
                                                                method="POST">

                                                                @csrf

                                                                @method('PUT')

                                                                <div class="modal-body">

                                                                    <div class="mb-3">

                                                                        <label>

                                                                            Date réelle réception

                                                                        </label>

                                                                        <input type="date" name="date_livraison"
                                                                            class="form-control" required
                                                                            value="{{ date('Y-m-d') }}">

                                                                    </div>

                                                                    <table class="table table-bordered">

                                                                        <thead>

                                                                            <tr>

                                                                                <th>

                                                                                    Article

                                                                                </th>

                                                                                <th>

                                                                                    Commandée

                                                                                </th>

                                                                                <th>

                                                                                    Livrée

                                                                                </th>

                                                                                <th>

                                                                                    Restante

                                                                                </th>

                                                                                <th>

                                                                                    Réception du jour

                                                                                </th>

                                                                            </tr>

                                                                        </thead>

                                                                        <tbody>

                                                                            @foreach ($commande->articles as $ligne)
                                                                                @if ($ligne->quantite_restante > 0)
                                                                                    <tr>

                                                                                        <td>

                                                                                            {{ $ligne->article->libelle }}

                                                                                        </td>

                                                                                        <td>

                                                                                            {{ $ligne->quantite }}

                                                                                        </td>

                                                                                        <td>

                                                                                            {{ $ligne->quantite_livree }}

                                                                                        </td>

                                                                                        <td>

                                                                                            <span class="badge bg-warning">

                                                                                                {{ $ligne->quantite_restante }}

                                                                                            </span>

                                                                                        </td>

                                                                                        <td>

                                                                                            <input type="number"
                                                                                                class="form-control"
                                                                                                name="quantites[{{ $ligne->id }}]"
                                                                                                value="0"
                                                                                                min="0"
                                                                                                max="{{ $ligne->quantite_restante }}">

                                                                                        </td>

                                                                                    </tr>
                                                                                @endif
                                                                            @endforeach

                                                                        </tbody>

                                                                    </table>

                                                                </div>

                                                                <div class="modal-footer">

                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">

                                                                        Annuler

                                                                    </button>

                                                                    <button type="submit" class="btn btn-success">

                                                                        Enregistrer réception

                                                                    </button>

                                                                </div>

                                                            </form>

                                                        </div>

                                                    </div>

                                                </div>
                                            @endif
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
