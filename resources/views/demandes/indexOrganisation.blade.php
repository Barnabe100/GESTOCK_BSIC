@extends('includes.master')

@section('title', auth()->user()->estAuSiege() ? 'Demandes du département' : 'Demandes de l\'agence')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    {{ auth()->user()->estAuSiege()
                        ? 'Demandes du : ' . auth()->user()->departement->nom
                        : 'Demandes de : ' . auth()->user()->agence->nom }}
                </h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">
                        <thead style="background:#c3c3ee">
                            <tr>
                                <th>#</th>
                                <th>Objet</th>
                                <th>Date</th>
                                <th>Demandeur</th>
                                <th>Validation</th>
                                <th>Livraison</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($demandes as $key => $demande)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $demande->objet }}</td>
                                    <td>{{ $demande->date }}</td>
                                    <td>{{ ($demande->user->prenom ?? '') . ' ' . ($demande->user->name ?? '') }}</td>
                                    <td>
                                        @if ($demande->validation_gestionnaire)
                                        <span class="badge bg-success">Validée</span>@else<span
                                                class="badge bg-warning">En attente</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($demande->livraison)
                                        <span class="badge bg-success">Livrée</span>@else<span
                                                class="badge bg-danger">Non livrée</span>
                                        @endif
                                    </td>
                                    <td>@include('includes.statut-demande')</td>
                                    <td>
                                        @if (auth()->user()->peutVoirDemande($demande))
                                            <a href="{{ route('demandes.show', $demande->id) }}"
                                                class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                        @endif

                                        @if (auth()->user()->peutConfirmerReception($demande))
                                            <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#receptionModal{{ $demande->id }}">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        @endif

                                        <div class="modal fade" id="receptionModal{{ $demande->id }}">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5>Retour réception</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('demandes.confirmer.reception', $demande->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <label>Votre retour</label>
                                                            <textarea name="commentaire" class="form-control" rows="4" required
                                                                placeholder="Bien reçu conforme / Quantité insuffisante / Article manquant..."></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-success">Envoyer</button>
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
        </div>
    </div>
@endsection
