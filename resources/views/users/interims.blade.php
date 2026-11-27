@extends('includes.master')

@section('title', 'Remplaçants temporaires de validation')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="mb-0">Remplaçants temporaires de validation</h5>

                <a href="{{ route('users.index') }}" class="btn btn-light btn-sm">
                    ← Retour
                </a>
            </div>

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($aSignaler->isNotEmpty())
                    <div class="alert alert-warning">
                        Entités avec un seul validateur supérieur :
                        @foreach ($aSignaler as $e)
                            <strong>{{ $e['libelle'] }}</strong>
                            ({{ $e['titulaire']->name }} {{ $e['titulaire']->prenom }}{{ $e['interim'] ? ', intérim en vigueur' : '' }}){{ $loop->last ? '.' : ',' }}
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('interims.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Entité concernée</label>
                            <span class="text-danger">*</span>
                            <select name="entite" class="form-control" required>
                                <option value=""></option>
                                @foreach ($entites as $cle => $libelle)
                                    <option value="{{ $cle }}" {{ old('entite') === $cle ? 'selected' : '' }}>{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Remplaçant (validateur supérieur d'une autre entité)</label>
                            <span class="text-danger">*</span>
                            <select name="user_id" class="form-control" required>
                                <option value=""></option>
                                @foreach ($remplacants as $r)
                                    <option value="{{ $r->id }}" {{ old('user_id') == $r->id ? 'selected' : '' }}>
                                        {{ $r->name }} {{ $r->prenom }}
                                        — {{ $r->agence->nom ?? '' }}{{ $r->departement ? ' / ' . $r->departement->nom : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-3 mb-3">
                            <label>Du</label>
                            <span class="text-danger">*</span>
                            <input type="date" name="debut_le" class="form-control" required
                                value="{{ old('debut_le', date('Y-m-d')) }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Au</label>
                            <span class="text-danger">*</span>
                            <input type="date" name="fin_le" class="form-control" required value="{{ old('fin_le') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Motif</label>
                            <input type="text" name="motif" class="form-control" value="{{ old('motif') }}">
                        </div>

                    </div>

                    <button class="btn btn-success">Enregistrer</button>

                </form>

                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-striped">

                        <thead style="background-color: #c3c3ee;">
                            <tr>
                                <th>Entité</th>
                                <th>Remplaçant</th>
                                <th>Période</th>
                                <th>Motif</th>
                                <th>Désigné par</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($interims as $i)
                                <tr>
                                    <td>{{ $i->libelleEntite() }}</td>
                                    <td>{{ $i->remplacant->name ?? '' }} {{ $i->remplacant->prenom ?? '' }}</td>
                                    <td>{{ $i->debut_le->format('d/m/Y') }} – {{ $i->fin_le->format('d/m/Y') }}</td>
                                    <td>{{ $i->motif }}</td>
                                    <td>{{ $i->auteur->name ?? '' }} {{ $i->auteur->prenom ?? '' }}</td>
                                    <td>
                                        @if ($i->revoque_le)
                                            <span class="badge bg-secondary">Révoqué</span>
                                        @elseif ($i->estEnVigueur())
                                            <span class="badge bg-success">En vigueur</span>
                                        @elseif ($i->fin_le->lt(now()->startOfDay()))
                                            <span class="badge bg-light text-dark">Terminé</span>
                                        @else
                                            <span class="badge bg-info">À venir</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!$i->revoque_le && !$i->fin_le->lt(now()->startOfDay()))
                                            <form action="{{ route('interims.revoquer', $i->id) }}" method="POST"
                                                style="display:inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" title="Révoquer" class="btn btn-danger btn-sm"
                                                    onclick="confirmAction(this.form, 'Révoquer cet intérim ?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Aucun intérim.</td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    </div>

@endsection
