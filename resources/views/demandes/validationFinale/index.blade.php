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

                @if (session('success'))
                    <div class="alert alert-success">

                        {{ session('success') }}

                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">

                        {{ session('error') }}

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

                                <th>Agence</th>

                                <th>Statut</th>

                                <th width="180">

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

                                        {{ $demande->user->agence->nom ?? '-' }}

                                    </td>

                                    <td>

                                        @include('includes.statut-demande')

                                    </td>

                                    <td class="text-nowrap">

                                        <!-- détail -->

                                        <a href="{{ route('demandes.show', $demande->id) }}" class="btn btn-info btn-sm">

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

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="text-center">

                                        Aucune demande en attente de validation finale

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
