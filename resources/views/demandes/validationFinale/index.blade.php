@extends('includes.master')

@section('title', 'Validation Finale')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">

                <h5>

                    Demandes en attente de validation finale

                </h5>

            </div>

            <div class="card-body">

                {{-- @if (session('success'))
                    <div class="alert alert-success">

                        {{ session('success') }}

                    </div>
                @endif --}}

                @if (session('error'))
                    <div class="alert alert-danger">

                        {{ session('error') }}

                    </div>
                @endif

                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">

                    <li class="nav-item" role="presentation">

                        <button class="nav-link active" id="attente-tab" data-bs-toggle="pill" data-bs-target="#attente"
                            type="button">

                            À valider

                            <span class="badge bg-warning text-dark ms-1">

                                {{ $enAttente->count() }}

                            </span>

                        </button>

                    </li>

                    <li class="nav-item" role="presentation">

                        <button class="nav-link" id="validees-tab" data-bs-toggle="pill" data-bs-target="#validees"
                            type="button">

                            Validées

                            <span class="badge bg-success ms-1">

                                {{ $validees->count() }}

                            </span>

                        </button>

                    </li>

                    <li class="nav-item" role="presentation">

                        <button class="nav-link" id="rejetees-tab" data-bs-toggle="pill" data-bs-target="#rejetees"
                            type="button">

                            Rejetées

                            <span class="badge bg-danger ms-1">

                                {{ $rejetees->count() }}

                            </span>

                        </button>

                    </li>

                </ul>

                <div class="tab-content" id="pills-tabContent">

                    <div class="tab-pane fade show active" id="attente" role="tabpanel">

                        <!-- LE TABLEAU ACTUEL SERA ICI -->

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover datatable">

                                <thead class="table-primary">

                                    <tr>

                                        <th>#</th>

                                        <th>Date</th>

                                        <th>Objet</th>

                                        <th>Demandeur</th>

                                        <th>Département</th>

                                        <th>Agence</th>

                                        <th>Statut</th>

                                        <th width="180">

                                            Actions

                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    {{-- @forelse($demandes as $demande) --}}
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

                                                {{ $demande->user->agence->nom ?? '-' }}

                                            </td>

                                            <td>

                                                @include('includes.statut-demande')

                                            </td>

                                            <td class="text-nowrap">

                                                <!-- détail -->

                                                <a href="{{ route('demandes.show', $demande->id) }}"
                                                    class="btn btn-info btn-sm">

                                                    <i class="fas fa-eye"></i>

                                                </a>

                                                <!-- validation finale -->

                                                <form action="{{ route('demandes.validation.finale', $demande->id) }}"
                                                    method="POST" class="d-inline">

                                                    @csrf

                                                    @method('PUT')

                                                    {{-- <button class="btn btn-success btn-sm"
                                                onclick="return confirm('Effectuer la validation finale ?')">

                                                <i class="fas fa-check"></i>

                                            </button> --}}

                                                    <button type="button" title="Valider" class="btn btn-success btn-sm"
                                                        onclick="confirmAction(this.form, 'Effectuer la validation finale ?')">

                                                        <i class="fas fa-check"></i>
                                                    </button>

                                                </form>

                                                <!-- rejet final -->

                                                <button type="button" title="Rejeter" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#rejetModal{{ $demande->id }}">

                                                    <i class="fas fa-times"></i>

                                                </button>

                                            </td>

                                        </tr>


                                        <div class="modal fade" id="rejetModal{{ $demande->id }}" tabindex="-1">

                                            <div class="modal-dialog">

                                                <div class="modal-content">

                                                    <form action="{{ route('demandes.rejet.finale', $demande->id) }}"
                                                        method="POST">

                                                        @csrf

                                                        @method('PUT')

                                                        <div class="modal-header bg-danger text-white">

                                                            <h5>

                                                                Rejet de la demande

                                                            </h5>

                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal">

                                                            </button>

                                                        </div>

                                                        <div class="modal-body">

                                                            <label>

                                                                Motif du rejet

                                                                <span class="text-danger">*</span>

                                                            </label>

                                                            <textarea name="motif_rejet" class="form-control" rows="4" required></textarea>

                                                        </div>

                                                        <div class="modal-footer">

                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">

                                                                Annuler

                                                            </button>

                                                            <button type="submit" class="btn btn-danger">

                                                                Rejeter

                                                            </button>

                                                        </div>

                                                    </form>

                                                </div>

                                            </div>

                                        </div>

                                    @empty

                                        {{-- <tr>

                                            <td colspan="8" class="text-center">

                                                Aucune demande en attente de validation finale

                                            </td>

                                        </tr> --}}
                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                    <div class="tab-pane fade" id="validees" role="tabpanel">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover datatable">

                                <thead class="table-success">

                                    <tr>

                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Objet</th>
                                        <th>Demandeur</th>
                                        <th>Département</th>
                                        <th>Agence</th>
                                        <th>Statut</th>
                                        <th>Actions</th>

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
                                                {{ $demande->user->agence->nom ?? '-' }}
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

                                            <td colspan="8" class="text-center">

                                                Aucune demande validée

                                            </td>

                                        </tr> --}}
                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                    <div class="tab-pane fade" id="rejetees" role="tabpanel">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover datatable">

                                <thead class="table-danger">

                                    <tr>

                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Objet</th>
                                        <th>Demandeur</th>
                                        <th>Département</th>
                                        <th>Agence</th>
                                        <th>Statut</th>
                                        <th>Actions</th>

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
                                                {{ $demande->user->agence->nom ?? '-' }}
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

                                            <td colspan="8" class="text-center">

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
