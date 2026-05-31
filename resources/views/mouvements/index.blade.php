@extends('includes.master')

@section('title', 'Mouvements de stock')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">

                <h5>

                    Historique des mouvements de stock

                </h5>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead class="table-primary">

                            <tr>

                                <th>#</th>

                                <th>Date</th>

                                <th>Article</th>

                                <th>Type</th>

                                <th>Quantité</th>
                                {{-- <th>Stock disponible</th> --}}

                                <th>Utilisateur</th>

                                <th>Motif</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($mouvements as $mouvement)
                                <tr>

                                    <td>

                                        {{ $loop->iteration }}

                                    </td>

                                    <td>

                                        {{ \Carbon\Carbon::parse($mouvement->date_mouvement)->format('d/m/Y') }}

                                    </td>

                                    <td>

                                        {{ $mouvement->article->libelle ?? '-' }}

                                    </td>


                                    <td>
                                        @if ($mouvement->type == 'ENTREE')
                                            <span class="badge bg-success">
                                                Entrée
                                            </span>
                                        @elseif($mouvement->type == 'SORTIE')
                                            <span class="badge bg-danger">
                                                Sortie
                                            </span>
                                        @elseif($mouvement->type == 'REBUT')
                                            <span class="badge bg-dark">
                                                Rebut
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                Ajustement

                                            </span>
                                        @endif

                                    </td>

                                    <td>

                                        {{ $mouvement->quantite }}

                                    </td>

                                    {{-- <td>

                                        {{ $mouvement->article->stock_disponible }}

                                    </td> --}}

                                    <td>

                                        {{ $mouvement->user->prenom ?? '' }}

                                        {{ $mouvement->user->name ?? '' }}

                                    </td>

                                    <td>

                                        {{ $mouvement->motif }}

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center">

                                        Aucun mouvement

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
