<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>

        Etat des sorties de stock

    </title>

    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        .header-table td {
            border: none;
        }

        h3 {
            margin: 0;
        }
    </style>

</head>

<body>

    <table class="header-table">

        <tr>

            <td width="20%">

                <img src="{{ public_path('assets/BSICBF-1.png') }}" width="100">
                <!-- LOGO -->

            </td>
            @php
                use Carbon\Carbon;
            @endphp

            <td width="80%" align="center">

                <h3>

                    ETAT DES SORTIES DE STOCK

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

        <thead>

            <tr>

                <th rowspan="2">

                    DATE

                </th>

                <th rowspan="2">

                    INTITULE

                </th>

                <th colspan="2">

                    SORTIE DE LA PERIODE

                </th>

            </tr>

            <tr>

                <th>

                    QUANTITE

                </th>

                <th>

                    VALEUR

                </th>

            </tr>

        </thead>

        <tbody>

            @if (count($data) == 0)

                <tr>

                    <td colspan="3"
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
            @else
                @foreach (collect($data)->groupBy('article') as $article => $lignes)
                    <tr>

                        <td colspan="4"
                            style="
                            font-weight:bold;
                            background-color:#f2f2f2;
                            text-align:left;
                        ">

                            INTITULE :
                            {{ strtoupper($article) }}

                        </td>

                    </tr>

                    @foreach ($lignes as $ligne)
                        <tr>

                            <td>{{ $ligne['date'] }}</td>

                            <td>{{ $ligne['article'] }}</td>

                            <td>{{ $ligne['quantite'] }}</td>

                            <td>{{ number_format($ligne['valeur'], 2, ',', ' ') }}</td>

                        </tr>
                    @endforeach
                @endforeach

            @endif

        </tbody>

    </table>

</body>

</html>
