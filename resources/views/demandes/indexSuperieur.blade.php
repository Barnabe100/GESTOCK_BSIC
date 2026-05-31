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

                @if (session('success'))
                    <div class="alert alert-success">

                        {{ session('success') }}

                    </div>
                @endif

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

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

                            @forelse($demandes as $demande)
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

                                        <a href="{{ route('demandes.show', $demande->id) }}" class="btn btn-info btn-sm">

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

                                                        <button type="button" class="btn-close" data-bs-dismiss="modal">

                                                        </button>

                                                    </div>

                                                    <form action="{{ route('demandes.rejeter.superieur', $demande->id) }}"
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

                                <tr>

                                    <td colspan="7" class="text-center">

                                        Aucune demande à valider

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection
