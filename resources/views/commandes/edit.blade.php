@extends('includes.master')

@section('title', 'Modifier commande')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Modifier commande</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('commandes.update', $commande->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- LIGNE 1 -->
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Numéro BC</label>
                            <input type="text" name="numero_bon_commande" class="form-control"
                                value="{{ old('numero_bon_commande', $commande->numero_bon_commande) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Numéro facture</label>
                            <input type="text" name="num_facture_proforma" class="form-control"
                                value="{{ old('num_facture_proforma', $commande->num_facture_proforma) }}">
                        </div>

                    </div>

                    <!-- LIGNE 2 -->
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ old('date', $commande->date) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Montant</label>
                            <input type="number" name="montant" class="form-control"
                                value="{{ old('montant', $commande->montant) }}">
                        </div>

                    </div>

                    <!-- LIGNE 3 -->
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Fournisseur</label>
                            <select name="fournisseur_id" class="form-control">

                                @foreach ($fournisseurs as $f)
                                    <option value="{{ $f->id }}"
                                        {{ $f->id == $commande->fournisseur_id ? 'selected' : '' }}>
                                        {{ $f->nom }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Libellé</label>
                            <input type="text" name="libelle" class="form-control"
                                value="{{ old('libelle', $commande->libelle) }}">
                        </div>

                    </div>

                    <!-- BOUTONS -->
                    <div style="display: flex; gap: 10px;">



                        <button class="btn btn-success">
                            Enregistrer
                        </button>

                        <a href="{{ route('commandes.index') }}" class="btn btn-secondary">
                            Retour
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection
