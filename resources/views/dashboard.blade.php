@extends('includes.master')

@section('title', 'Dashboard')

@section('content')

    <div class="container-fluid mt-4">

        <!-- 🔥 STATISTIQUES -->
        <div class="row">

            <!-- USERS -->
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm border-0 bg-primary text-white">

                    <div class="card-body">

                        <h6>Utilisateurs</h6>

                        <h2>{{ $totalUsers }}</h2>

                    </div>

                </div>

            </div>

            <!-- DEMANDES -->
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm border-0 bg-success text-white">

                    <div class="card-body">

                        <h6>Demandes</h6>

                        <h2>{{ $totalDemandes }}</h2>

                    </div>

                </div>

            </div>

            <!-- COMMANDES -->
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm border-0 bg-warning text-dark">

                    <div class="card-body">

                        <h6>Commandes</h6>

                        <h2>{{ $totalCommandes }}</h2>

                    </div>

                </div>

            </div>

            <!-- MONTANT TOTAL COMMANDES -->
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm border-0 bg-dark text-white">

                    <div class="card-body">

                        <h6>Montant commandes</h6>

                        <h3>{{ number_format($totalMontantCommandes, 0, ',', ' ') }}</h3>

                    </div>

                </div>

            </div>

            <!-- STOCK -->
            {{-- <div class="col-md-3 mb-3">

                <div class="card shadow-sm border-0 bg-danger text-white">

                    <div class="card-body">

                        <h6>Stock critique</h6>

                        <h2>{{ $stockCritique }}</h2>

                    </div>

                </div>

            </div>

        </div> --}}

            <!-- 🔥 TABLEAUX -->
            <div class="row">

                <!-- DEMANDES -->
                <div class="col-md-6 mb-4">

                    <div class="card shadow-sm">

                        <div class="card-header bg-primary text-white">
                            Dernières demandes
                        </div>

                        <div class="card-body">

                            <table class="table table-bordered">

                                <thead>
                                    <tr>
                                        <th>Objet</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($dernieresDemandes as $demande)
                                        <tr>

                                            <td>{{ $demande->objet }}</td>

                                            <td>{{ $demande->date }}</td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                <!-- COMMANDES -->
                <div class="col-md-6 mb-4">

                    <div class="card shadow-sm">

                        <div class="card-header bg-success text-white">
                            Dernières commandes
                        </div>

                        <div class="card-body">

                            <table class="table table-bordered">

                                <thead>
                                    <tr>
                                        <th>Numéro BC</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($dernieresCommandes as $commande)
                                        <tr>

                                            <td>{{ $commande->numero_bon_commande }}</td>

                                            <td>{{ $commande->date }}</td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

            <!-- 🔥 ARTICLES CRITIQUES -->
            <div class="card shadow-sm">

                <div class="card-header bg-danger text-white">
                    Articles en stock critique
                </div>

                <div class="card-body">

                    <table class="table table-bordered">

                        <thead>

                            <tr>

                                <th>Article</th>

                                <th>Stock disponible</th>

                                <th>Stock sécurité</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($articlesCritiques as $article)
                                <tr class="table-danger">

                                    <td>{{ $article->libelle }}</td>

                                    <td>{{ $article->stock_disponible }}</td>

                                    <td>{{ $article->stock_securite }}</td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="3" class="text-center">

                                        Aucun article critique

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- TOP ARTICLES DEMANDES -->
            <div class="card shadow-sm mt-4">

                <div class="card-header bg-dark text-white">
                    Articles les plus demandés
                </div>

                <div class="card-body">

                    <table class="table table-bordered">

                        <thead>

                            <tr>

                                <th>Article</th>

                                <th>Quantité</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($topArticles as $article)
                                <tr>

                                    <td>{{ $article->libelle }}</td>

                                    <td>{{ $article->total }}</td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

            {{-- <div class="row mt-4">

            <!-- DEMANDES MOIS-->
            <div class="col-md-6 mb-4">

                <div class="card shadow-sm">

                    <div class="card-header bg-primary text-white">
                        Demandes par mois
                    </div>

                    <div class="card-body">

                        <canvas id="demandesChart"></canvas>

                    </div>

                </div>

            </div> --}}

            <!-- COMMANDES PAR MOIS-->
            {{-- <div class="col-md-6 mb-4">

            <div class="card shadow-sm">

                <div class="card-header bg-success text-white">
                    Commandes par mois
                </div>

                <div class="card-body">

                    <canvas id="commandesChart"></canvas>

                </div>

            </div>

        </div> --}}

        </div>

    </div>


@endsection

<script>
    // 🔥 DEMANDES
    new Chart(document.getElementById('demandesChart'), {

        type: 'bar',

        data: {

            labels: {!! json_encode($demandesParMois->keys()) !!},

            datasets: [{
                label: 'Demandes',
                data: {!! json_encode($demandesParMois->values()) !!}
            }]
        }
    });

    // 🔥 COMMANDES
    new Chart(document.getElementById('commandesChart'), {

        type: 'line',

        data: {

            labels: {!! json_encode($commandesParMois->keys()) !!},

            datasets: [{
                label: 'Commandes',
                data: {!! json_encode($commandesParMois->values()) !!}
            }]
        }
    });
</script>
