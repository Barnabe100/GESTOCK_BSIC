<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">

    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .header-table td {
            border: none;
        }

        .agence-row {
            font-weight: bold;
            background-color: #f2f2f2;
            text-align: left;
        }
    </style>

</head>

<body>



    <table class="header-table">

        <tr>

            <td width="20%">

                <img src="{{ public_path('assets/BSICBF-1.png') }}" width="100">

            </td>

            @php
                use Carbon\Carbon;
            @endphp

            <td width="80%">

                <h3>

                    ETAT DES DEMANDES PAR DEPARTEMENT

                </h3>

                <strong>
                    DU
                    {{ Carbon::parse($dateDebut)->format('d/m/Y') }}
                    AU
                    {{ Carbon::parse($dateFin)->format('d/m/Y') }}
                </strong>

            </td>

        </tr>

    </table>

    <table>

        {{-- <thead>

            <tr>

                <th>DATE</th>
                <th>ARTICLE</th>
                <th>UTILISATEUR</th>
                <th>NB DEMANDÉ</th>
                <th>NB ACCORDÉ</th>
                <th>CMP</th>
                <th>VALEUR</th>

            </tr>

        </thead> --}}

        <tbody>

            @if ($demandes->count())

                @foreach ($demandes->groupBy(fn($d) => $d->user->departement->nom ?? 'Sans département') as $departement => $listeDemandes)
                    <tr>

                        <td colspan="7" class="agence-row">

                            DEPARTEMENT :
                            {{ strtoupper($departement) }}

                        </td>

                    </tr>

                    @php

                        $categories = [];

                        foreach ($listeDemandes as $demande) {
                            foreach ($demande->articles as $ligne) {
                                $categorie = $ligne->article->categorie->libelle ?? 'Sans catégorie';

                                $categories[$categorie][] = [
                                    'date' => $demande->date,

                                    'article' => $ligne->article->libelle,

                                    'utilisateur' =>
                                        ($demande->user->prenom ?? '') . ' ' . ($demande->user->name ?? ''),

                                    'demande' => $ligne->quantite,

                                    'accorde' => $ligne->quantite_accordee,

                                    'cmp' => $ligne->article->cmp ?? 0,

                                    'valeur' => ($ligne->quantite_accordee ?? 0) * ($ligne->article->cmp ?? 0),
                                ];
                            }
                        }

                        /*
                        Tri alphabétique des catégories
                        */

                        ksort($categories);

                        $totalAgence = 0;
                    @endphp

                    @foreach ($categories as $categorie => $lignes)
                        @php

                            usort($lignes, function ($a, $b) {
                                return strcmp($a['date'], $b['date']);
                            });

                        @endphp
                        <tr>

                            <td colspan="7"
                                style="
                        background:#f2f2f2;
                        font-weight:bold;
                        text-align:left;
                        
                    ">

                                CATÉGORIE :
                                {{ strtoupper($categorie) }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                DATE

                            </th>

                            <th>

                                ARTICLE

                            </th>

                            <th>

                                UTILISATEUR

                            </th>

                            <th>

                                NB DEMANDÉ

                            </th>

                            <th>

                                NB ACCORDÉ

                            </th>

                            <th>

                                CMP

                            </th>

                            <th>

                                VALEUR

                            </th>

                        </tr>

                        @php

                            $totalCategorie = 0;

                        @endphp

                        @foreach ($lignes as $ligne)
                            <tr>

                                <td>

                                    {{ $ligne['date'] }}

                                </td>

                                <td>

                                    {{ $ligne['article'] }}

                                </td>

                                <td>

                                    {{ $ligne['utilisateur'] }}

                                </td>

                                <td>

                                    {{ $ligne['demande'] }}

                                </td>

                                <td>

                                    {{ $ligne['accorde'] }}

                                </td>

                                <td>

                                    {{ number_format($ligne['cmp'], 0, ',', ' ') }}

                                </td>

                                <td>

                                    {{ number_format($ligne['valeur'], 0, ',', ' ') }}

                                </td>

                            </tr>

                            @php

                                $totalCategorie += $ligne['valeur'];

                            @endphp
                        @endforeach

                        <tr>

                            <td colspan="6"
                                style="
                        text-align:left;
                        font-weight:bold;
                        background:#dcebff;
                        
                    ">

                                TOTAL CATÉGORIE

                            </td>

                            <td
                                style="
                        font-weight:bold;
                        background:#dcebff;
                        
                    ">

                                {{ number_format($totalCategorie, 0, ',', ' ') }}

                            </td>

                        </tr>

                        @php

                            $totalAgence += $totalCategorie;

                        @endphp
                    @endforeach

                    <tr>

                        <td colspan="6"
                            style="
                    text-align:left;
                    font-weight:bold;
                    background:#a6cdff;
                ">

                            TOTAL DES DEMANDES DU {{ strtoupper($departement) }}

                        </td>

                        <td
                            style="
                    font-weight:bold;
                    background:#a6cdff;
                ">

                            {{ number_format($totalAgence, 0, ',', ' ') }}

                        </td>

                    </tr>
                @endforeach
            @else
                <tr>

                    <td colspan="7"
                        style="
                font-weight:bold;
                background-color:#f2f2f2;
                color:#c0392b;
                text-align:center;
            ">

                        AUCUNE DONNÉE DISPONIBLE
                        POUR LA PÉRIODE SÉLECTIONNÉE

                    </td>

                </tr>

            @endif

        </tbody>

    </table>

</body>


</html>
