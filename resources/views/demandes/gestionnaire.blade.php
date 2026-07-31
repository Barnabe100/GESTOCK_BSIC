@extends('includes.master')

@section('title', 'Liste des demandes')

@section('content')




    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0">Liste des demandes</h5>
                @if (auth()->user()->hasAnyRole(['SuperAdmin', 'Admin', 'Gestionnaire']))
                    @if (!($estMesDemandes ?? false))
                        <div class="d-flex gap-2">
                            <!-- EXCEL -->
                            <a href="{{ route('demandes.export.excel') }}" class="btn btn-success btn-sm">

                                <i class="fas fa-file-excel"></i>
                                Excel
                            </a>

                            <!-- CSV -->
                            <a href="{{ route('demandes.export.csv') }}" class="btn btn-info btn-sm">

                                <i class="fas fa-file-csv"></i>
                                CSV
                            </a>
                    @endif
                @endif

                <a href="{{ route('demandes.create') }}" class="btn btn-light btn-sm">
                    + Ajouter
                </a>
            </div>
        </div>

        <!-- BODY -->
        <div class="card-body">

            <ul class="nav nav-pills mb-3">

                <li class="nav-item">

                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#attente">

                        À traiter

                        <span class="badge bg-warning text-dark ms-1">

                            {{ $enAttente->count() }}

                        </span>

                    </button>

                </li>

                <li class="nav-item">

                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#traitees">

                        Traitées

                        <span class="badge bg-success ms-1">

                            {{ $traitees->count() }}

                        </span>

                    </button>

                </li>

                <li class="nav-item">

                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#rejetees">

                        Rejetées

                        <span class="badge bg-danger ms-1">

                            {{ $rejetees->count() }}

                        </span>

                    </button>

                </li>

                <li class="nav-item">

                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#enAttenteLivraison">

                        À livrer

                        <span class="badge bg-info ms-1">

                            {{ $enAttenteLivraison->count() }}

                        </span>

                    </button>

                </li>

                <li class="nav-item">

                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#enAttenteReception">

                        En attente réception

                        <span class="badge bg-info ms-1">

                            {{ $enAttenteReception->count() }}

                        </span>

                    </button>

                </li>

                <li class="nav-item">

                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#cloturees">

                        Receptionnées

                        <span class="badge bg-primary ms-1">

                            {{ $cloturees->count() }}

                        </span>

                    </button>

                </li>

            </ul>


            <div class="tab-content">

                <div class="tab-pane fade show active" id="attente">

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatable">

                            <thead style="background-color: #c3c3ee;">
                                <tr>
                                    <th>#</th>
                                    <th>Objet</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Validation</th>
                                    <th>Livraison</th>
                                    <th>Statut</th>
                                    <th width="100px">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($enAttente as $key => $demande)
                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <td>{{ $demande->objet }}</td>

                                        <td>{{ $demande->date }}</td>

                                        <td>{{ $demande->user->name ?? '' }} {{ $demande->user->prenom ?? '' }}</td>

                                        <td>
                                            @if ($demande->validation_gestionnaire)
                                                <span class="badge bg-success">Validée</span>
                                            @else
                                                <span class="badge bg-warning">En attente</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($demande->livraison)
                                                <span class="badge bg-success">Livrée</span>
                                            @else
                                                <span class="badge bg-danger">Non livrée</span>
                                            @endif
                                        </td>

                                        <td>

                                            @include('includes.statut-demande')

                                        </td>

                                        <td>
                                            @if (
                                                $demande->user_id == auth()->id() ||
                                                    auth()->user()->hasAnyRole(['SuperAdmin', 'Gestionnaire']))
                                                <a href="{{ route('demandes.show', $demande->id) }}" title="Détail"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endif
                                            @if (
                                                $demande->user_id == auth()->id() ||
                                                    auth()->user()->hasAnyRole(['SuperAdmin']))
                                                @if (!$demande->validation_superieur)
                                                    <a href="{{ route('demandes.edit', $demande->id) }}" title="Editer"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                            @endif

                                            @role('SuperAdmin')
                                                <form action="{{ route('demandes.destroy', $demande->id) }}" method="POST"
                                                    style="display:inline-block">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button" title="Supprimer" class="btn btn-danger btn-sm"
                                                        onclick="confirmDelete(this.form)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>

                                                </form>
                                            @endrole

                                            @if (auth()->user()->hasAnyRole(['Gestionnaire', 'SuperAdmin']))
                                                @if (!$demande->validation_gestionnaire)
                                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                                        data-bs-target="#validationModal{{ $demande->id }}"
                                                        title="Valider">

                                                        <i class="fas fa-check"></i>

                                                    </button>
                                                @endif




                                                <div class="modal fade" id="validationModal{{ $demande->id }}"
                                                    tabindex="-1">

                                                    <div class="modal-dialog modal-lg">

                                                        <div class="modal-content">

                                                            <div class="modal-header bg-success text-white">

                                                                <h5>

                                                                    Validation gestionnaire

                                                                </h5>

                                                                <button class="btn-close" data-bs-dismiss="modal">

                                                                </button>

                                                            </div>

                                                            <form action="{{ route('demandes.valider', $demande->id) }}"
                                                                method="POST">

                                                                @csrf
                                                                @method('PUT')

                                                                <div class="modal-body">

                                                                    <table class="table">

                                                                        <thead>

                                                                            <tr>

                                                                                <th>

                                                                                    Article

                                                                                </th>

                                                                                <th>

                                                                                    Demandée

                                                                                </th>

                                                                                <th>

                                                                                    Disponible

                                                                                </th>

                                                                                <th>

                                                                                    Accordée

                                                                                </th>

                                                                            </tr>

                                                                        </thead>

                                                                        <tbody>

                                                                            @foreach ($demande->articles as $item)
                                                                                <tr>

                                                                                    <td>

                                                                                        {{ $item->article->libelle }}

                                                                                    </td>

                                                                                    <td>

                                                                                        {{ $item->quantite }}

                                                                                    </td>

                                                                                    <td>

                                                                                        {{ $item->article->stock_disponible }}

                                                                                    </td>

                                                                                    <td>

                                                                                        <input type="number"
                                                                                            name="quantite_accordee[{{ $item->article->id }}]"
                                                                                            class="form-control"
                                                                                            min="0"
                                                                                            max="{{ min($item->quantite, $item->article->stock_disponible) }}"
                                                                                            value="{{ $item->quantite_accordee ?? min($item->quantite, $item->article->stock_disponible) }}">

                                                                                    </td>

                                                                                </tr>
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

                                                                        Valider

                                                                    </button>

                                                                </div>

                                                            </form>

                                                        </div>

                                                    </div>

                                                </div>
                                                {{-- Fin validation demande --}}

                                                {{-- Rejet demande --}}

                                                @if (!$demande->validation_gestionnaire && $demande->statut == 'En attente gestionnaire')
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                        data-bs-target="#rejetModal{{ $demande->id }}">

                                                        <i class="fas fa-times"></i>

                                                    </button>
                                                @endif
                                                <!-- Modal rejet -->

                                                <div class="modal fade" id="rejetModal{{ $demande->id }}"
                                                    tabindex="-1" aria-hidden="true">

                                                    <div class="modal-dialog">

                                                        <div class="modal-content">

                                                            <div class="modal-header bg-danger text-white">

                                                                <h5 class="modal-title">

                                                                    Motif du rejet

                                                                </h5>

                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal">

                                                                </button>

                                                            </div>

                                                            <form
                                                                action="{{ route('demandes.rejeter.gestionnaire', $demande->id) }}"
                                                                method="POST">

                                                                @csrf
                                                                @method('PUT')

                                                                <div class="modal-body">

                                                                    <label>

                                                                        Veuillez renseigner le motif :

                                                                    </label>

                                                                    <textarea name="commentaire" class="form-control" rows="4" required></textarea>

                                                                </div>

                                                                <div class="modal-footer">

                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">

                                                                        Annuler

                                                                    </button>

                                                                    <button class="btn btn-danger">

                                                                        Confirmer

                                                                    </button>

                                                                </div>

                                                            </form>

                                                        </div>

                                                    </div>

                                                </div>
                                            @endif

                                            @if (
                                                ($estMesDemandes ?? false) &&
                                                    $demande->user_id == auth()->id() &&
                                                    $demande->statut == 'En attente accord demandeur')
                                                <form action="{{ route('demandes.valider.demandeur', $demande->id) }}"
                                                    method="POST" class="d-inline">

                                                    @csrf
                                                    @method('PUT')

                                                    <button class="btn btn-success btn-sm" title="Accepter">

                                                        <i class="fas fa-check-circle"></i>

                                                    </button>

                                                </form>
                                            @endif

                                            {{-- confirmation demande recepteur --}}
                                            @if ($demande->statut == 'En attente réception demandeur' && $demande->user_id == auth()->id())
                                                <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#receptionModal{{ $demande->id }}"
                                                    title="Confirmer réception">

                                                    <i class="fas fa-check-circle"></i>


                                                </button>
                                            @endif

                                            <div class="modal fade" id="receptionModal{{ $demande->id }}">

                                                <div class="modal-dialog">

                                                    <div class="modal-content">

                                                        <div class="modal-header bg-success text-white">

                                                            <h5>

                                                                Retour réception

                                                            </h5>

                                                        </div>


                                                        <form
                                                            action="{{ route('demandes.confirmer.reception', $demande->id) }}"
                                                            method="POST">

                                                            @csrf

                                                            @method('PUT')


                                                            <div class="modal-body">

                                                                <label>

                                                                    Votre retour :

                                                                </label>

                                                                <textarea name="commentaire" class="form-control" rows="4" required
                                                                    placeholder="Bien reçu conforme / Quantité insuffisante / Article manquant..."></textarea>

                                                            </div>


                                                            <div class="modal-footer">

                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">

                                                                    Annuler

                                                                </button>


                                                                <button type="submit" class="btn btn-success">

                                                                    Envoyer

                                                                </button>

                                                            </div>

                                                        </form>

                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                </div>

                {{-- Deamandes traitees --}}

                <div class="tab-pane fade" id="traitees">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead style="background-color: #d4edda;">

                                <tr>

                                    <th>#</th>
                                    <th>Objet</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th width="80">Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse ($traitees as $key => $demande)
                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <td>{{ $demande->objet }}</td>

                                        <td>{{ $demande->date }}</td>

                                        <td>
                                            {{ $demande->user->name ?? '' }} {{ $demande->user->prenom ?? '' }}
                                        </td>

                                        <td>

                                            @include('includes.statut-demande')

                                        </td>

                                        <td>

                                            <a href="{{ route('demandes.show', $demande->id) }}"
                                                class="btn btn-info btn-sm" title="Détail">

                                                <i class="fas fa-eye"></i>

                                            </a>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="text-center">

                                            Aucune demande traitée

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

                {{-- Demandes rejetees --}}

                <div class="tab-pane fade" id="rejetees">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead class="table-danger">

                                <tr>

                                    <th>#</th>
                                    <th>Objet</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th width="80">Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse ($rejetees as $key => $demande)
                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <td>{{ $demande->objet }}</td>

                                        <td>{{ $demande->date }}</td>

                                        <td>

                                            {{ $demande->user->name ?? '' }} {{ $demande->user->prenom ?? '' }}

                                        </td>

                                        <td>

                                            @include('includes.statut-demande')

                                        </td>

                                        <td>

                                            <a href="{{ route('demandes.show', $demande->id) }}"
                                                class="btn btn-info btn-sm" title="Détail">

                                                <i class="fas fa-eye"></i>

                                            </a>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="text-center">

                                            Aucune demande rejetée

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

                {{-- Demandes En attente de reception demandeur --}}


                <div class="tab-pane fade" id="enAttenteReception">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead class="table-primary">

                                <tr>

                                    <th>#</th>
                                    <th>Objet</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th width="80" class="text-nowrap">Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse ($enAttenteReception as $key => $demande)
                                    <small>
                                        VF={{ $demande->validation_finale }}
                                        |
                                        LIV={{ $demande->livraison }}
                                    </small>
                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <td>{{ $demande->objet }}</td>

                                        <td>{{ $demande->date }}</td>

                                        <td>

                                            {{ $demande->user->name ?? '' }} {{ $demande->user->prenom ?? '' }}

                                        </td>

                                        <td>

                                            @include('includes.statut-demande')

                                        </td>

                                        <td class="text-nowrap">

                                            <a href="{{ route('demandes.show', $demande->id) }}"
                                                class="btn btn-info btn-sm" title="Détail">

                                                <i class="fas fa-eye"></i>

                                            </a>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="text-center">

                                            Aucune demande en attente de livraison

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

                {{-- Demandes En attente de livraison --}}
                <div class="tab-pane fade" id="enAttenteLivraison">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead class="table-primary">

                                <tr>

                                    <th>#</th>
                                    <th>Objet</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th width="80" class="text-nowrap">Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse ($enAttenteLivraison as $key => $demande)
                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <td>{{ $demande->objet }}</td>

                                        <td>{{ $demande->date }}</td>

                                        <td>

                                            {{ $demande->user->name ?? '' }} {{ $demande->user->prenom ?? '' }}

                                        </td>

                                        <td>

                                            @include('includes.statut-demande')

                                        </td>

                                        <td class="text-nowrap">

                                            <a href="{{ route('demandes.show', $demande->id) }}"
                                                class="btn btn-info btn-sm" title="Détail">

                                                <i class="fas fa-eye"></i>

                                            </a>

                                            @if (auth()->user()->hasAnyRole(['Gestionnaire', 'SuperAdmin']))
                                                <form action="{{ route('demandes.livrer', $demande->id) }}"
                                                    method="POST" style="display:inline-block">

                                                    @csrf

                                                    <button type="button" title="Livrer" class="btn btn-success btn-sm"
                                                        onclick="confirmAction(
                                                            this.form,
                                                            'Confirmer la livraison de cette demande ?'
                                                        )">

                                                        <i class="fas fa-truck"></i>

                                                    </button>

                                                </form>
                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="text-center">

                                            Aucune demande en attente de livraison

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

                {{-- Demandes cloturees --}}

                <div class="tab-pane fade" id="cloturees">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead class="table-primary">

                                <tr>

                                    <th>#</th>
                                    <th>Objet</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th width="80">Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse ($cloturees as $key => $demande)
                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <td>{{ $demande->objet }}</td>

                                        <td>{{ $demande->date }}</td>

                                        <td>

                                            {{ $demande->user->name ?? '' }} {{ $demande->user->prenom ?? '' }}

                                        </td>

                                        <td>

                                            @include('includes.statut-demande')

                                        </td>

                                        <td>

                                            <a href="{{ route('demandes.show', $demande->id) }}"
                                                class="btn btn-info btn-sm" title="Détail">

                                                <i class="fas fa-eye"></i>

                                            </a>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="text-center">

                                            Aucune demande clôturée

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>



        </div>

    </div>

    </div>

@endsection
