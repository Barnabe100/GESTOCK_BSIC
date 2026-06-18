@extends('includes.master')

@section('title', 'Nouvelle commande')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Créer une commande</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('commandes.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Numéro bon commande</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="numero_bon_commande" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Numéro facture</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="num_facture_proforma" class="form-control" required>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Date</label>
                            <span class="text-danger">*</span>
                            <input type="date" name="date" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Libellé</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="libelle" class="form-control" required>
                        </div>



                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Fournisseur</label>
                            <span class="text-danger">*</span>
                            <select name="fournisseur_id" class="form-control select2" required>
                                <option value=""></option>
                                @foreach ($fournisseurs as $f)
                                    <option value="{{ $f->id }}">{{ $f->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Montant</label>
                            <span class="text-danger">*</span>
                            <input type="number" name="montant" class="form-control" required>
                        </div>



                    </div>

                    <button class="btn btn-success">Enregistrer</button>
                    <a href="{{ route('commandes.index') }}" class="btn btn-secondary">
                        Retour
                    </a>

                </form>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $('.select2').select2({
                placeholder: 'Sélectionner ...',
                allowClear: true,
                width: '100%'
            });

        });
    </script>
@endpush
