@extends('includes.master')

@section('title', 'Validation Supérieur')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">

                <h5>

                    Demandes en attente de validation

                </h5>

            </div>

            <div class="card-body">

                {{-- @if (session('success'))
                    <div class="alert alert-success">

                        {{ session('success') }}

                    </div>
                @endif --}}

                <ul class="nav nav-pills mb-3">

                    <li class="nav-item">

                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#attente">

                            À valider

                            <span class="badge bg-warning text-dark ms-1">

                                {{ $enAttente->count() }}

                            </span>

                        </button>

                    </li>

                    <li class="nav-item">

                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#validees">

                            Validées

                            <span class="badge bg-success ms-1">

                                {{ $validees->count() }}

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

                </ul>

                <div class="tab-content">

                    <div class="tab-pane fade show active" id="attente">

                        <div class="table-responsive">

                            <table id="table-attente" class="table table-bordered table-hover table-striped datatable">

                                <thead class="table-primary">

                                    <tr>

                                        <th>#</th>

                                        <th>Date</th>

                                        <th>Objet</th>

                                        <th>Demandeur</th>

                                        <th>Département</th>

                                        <th>Statut</th>

                                        <th width="220">

                                            Actions

                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($enAttente as $demande)
                                        <tr>

                                            <td>

                                                {{ $loop->iteration }}

                                            </td>

                                            <td>

                                                {{ $demande->date }}

                                            </td>

                                            <td>

                                                {{ $demande->objet }}

                                            </td>

                                            <td>

                                                {{ $demande->user->prenom }}

                                                {{ $demande->user->name }}

                                            </td>

                                            <td>

                                                {{ $demande->user->departement->nom ?? '-' }}

                                            </td>

                                            <td>

                                                @include('includes.statut-demande')

                                            </td>

                                            <td class="text-nowrap">

                                                <!-- Détails -->

                                                <a href="{{ route('demandes.show', $demande->id) }}"
                                                    class="btn btn-info btn-sm">

                                                    <i class="fas fa-eye"></i>

                                                </a>

                                                <!-- Valider -->

                                                <form action="{{ route('demandes.valider.superieur', $demande->id) }}"
                                                    method="POST" class="d-inline">

                                                    @csrf

                                                    @method('PUT')

                                                    {{-- <button class="btn btn-success btn-sm">

                                                <i class="fas fa-check"></i>

                                            </button> --}}

                                                    <button type="button" title="Valider" class="btn btn-success btn-sm"
                                                        onclick="confirmAction(this.form, 'Confirmer la validation de cette demande ?')">

                                                        <i class="fas fa-check"></i>
                                                    </button>

                                                </form>

                                                <!-- Rejeter -->
                                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#rejetModal{{ $demande->id }}">

                                                    <i class="fas fa-times"></i>

                                                </button>

                                                <!-- Modal rejet -->

                                                <div class="modal fade" id="rejetModal{{ $demande->id }}" tabindex="-1"
                                                    aria-hidden="true">

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
                                                                action="{{ route('demandes.rejeter.superieur', $demande->id) }}"
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

                                            </td>

                                        </tr>

                                    @empty

                                        {{-- <tr>

                                            <td colspan="7" class="text-center">

                                                Aucune demande à valider

                                            </td>

                                        </tr> --}}
                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                    {{-- Demandes validees --}}

                    <div class="tab-pane fade" id="validees">

                        <div class="table-responsive">

                            <table id="table-validees" class="table table-bordered table-hover table-striped datatable">
                                <thead class="table-success">

                                    <tr>

                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Objet</th>
                                        <th>Demandeur</th>
                                        <th>Département</th>
                                        <th>Statut</th>
                                        <th width="80">Actions</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($validees as $demande)
                                        <tr>

                                            <td>{{ $loop->iteration }}</td>

                                            <td>{{ $demande->date }}</td>

                                            <td>{{ $demande->objet }}</td>

                                            <td>

                                                {{ $demande->user->prenom }}

                                                {{ $demande->user->name }}

                                            </td>

                                            <td>

                                                {{ $demande->user->departement->nom ?? '-' }}

                                            </td>

                                            <td>

                                                @include('includes.statut-demande')

                                            </td>

                                            <td>

                                                <a href="{{ route('demandes.show', $demande->id) }}"
                                                    class="btn btn-info btn-sm">

                                                    <i class="fas fa-eye"></i>

                                                </a>

                                            </td>

                                        </tr>

                                    @empty

                                        {{-- <tr>

                                            <td colspan="7" class="text-center">

                                                Aucune demande validée

                                            </td>

                                        </tr> --}}
                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                    <div class="tab-pane fade" id="rejetees">

                        <div class="table-responsive">

                            <table id="table-rejetees" class="table table-bordered table-hover datatable">

                                <thead class="table-danger">

                                    <tr>

                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Objet</th>
                                        <th>Demandeur</th>
                                        <th>Département</th>
                                        <th>Statut</th>
                                        <th width="80">Actions</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($rejetees as $demande)
                                        <tr>

                                            <td>{{ $loop->iteration }}</td>

                                            <td>{{ $demande->date }}</td>

                                            <td>{{ $demande->objet }}</td>

                                            <td>

                                                {{ $demande->user->prenom }}

                                                {{ $demande->user->name }}

                                            </td>

                                            <td>

                                                {{ $demande->user->departement->nom ?? '-' }}

                                            </td>

                                            <td>

                                                @include('includes.statut-demande')

                                            </td>

                                            <td>

                                                <a href="{{ route('demandes.show', $demande->id) }}"
                                                    class="btn btn-info btn-sm">

                                                    <i class="fas fa-eye"></i>

                                                </a>

                                            </td>

                                        </tr>

                                    @empty

                                        {{-- <tr>

                                            <td colspan="7" class="text-center">

                                                Aucune demande rejetée

                                            </td>

                                        </tr> --}}
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
