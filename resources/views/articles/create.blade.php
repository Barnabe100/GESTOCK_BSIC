@extends('includes.master')

@section('title', 'Nouvel article')

@section('content')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5>Créer un article</h5>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('articles.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Code</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Libellé</label>
                            <span class="text-danger">*</span>
                            <input type="text" name="libelle" class="form-control" value="{{ old('libelle') }}" required>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Catégorie</label>
                            <span class="text-danger">*</span>
                            <select name="categorie_id" class="form-control select2" style="height: 42px;" required>
                                <option value=""></option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Prix unitaire</label>
                            {{-- <span class="text-danger">*</span> --}}
                            <input type="number" name="prix_unitaire" class="form-control">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Stock disponible</label>
                            {{-- <span class="text-danger">*</span> --}}
                            <input type="number" name="stock_disponible" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Stock sécurité</label>
                            {{-- <span class="text-danger">*</span> --}}
                            <input type="number" name="stock_securite" class="form-control">
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>
                    <a href="{{ route('articles.index') }}" class="btn btn-secondary">
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
                width: '100%',
                //height: 42 px!important;
            });

        });
    </script>
@endpush
