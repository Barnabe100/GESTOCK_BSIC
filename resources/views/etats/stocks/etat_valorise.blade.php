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
    </style>

</head>

<body>

    <table class="header-table">

        <tr>

            <td width="20%">

                <img src="{{ public_path('assets/BSICBF-1.png') }}" width="100">
                <!-- LOGO -->
                {{-- <img src="{{ asset('assets/bsic.png') }}" alt="Logo" class="img-fluid mb-4" width="210"> --}}

            </td>

            @php
                use Carbon\Carbon;
            @endphp

            <td width="80%">

                <h3>

                    ETAT VALORISE DES STOCKS

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

                {{-- <th rowspan="2">

                    DATE

                </th> --}}

                <th rowspan="2">

                    INTITULE

                </th>

                <th colspan="2">

                    STOCK EN DEBUT
                    DE PERIODE

                </th>

                <th colspan="2">

                    ENTREE DE LA
                    PERIODE

                </th>

                <th colspan="2">

                    SORTIE DE LA
                    PERIODE

                </th>

                <th colspan="2">

                    STOCK EN FIN
                    DE PERIODE

                </th>

            </tr>

            <tr>

                <th>

                    QTE

                </th>

                <th>

                    VALEUR

                </th>

                <th>

                    QTE

                </th>

                <th>

                    VALEUR

                </th>

                <th>

                    QTE

                </th>

                <th>

                    VALEUR

                </th>

                <th>

                    QTE

                </th>

                <th>

                    VALEUR

                </th>

            </tr>

        </thead>

        <tbody>

            @if (count($data) == 0)
                <tr>

                    <td colspan="4"
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

            @foreach ($data as $ligne)
                <tr>

                    {{-- <td>

                        {{ $ligne['date'] }}

                    </td> --}}

                    <td>

                        {{ $ligne['article'] }}

                    </td>

                    <td>

                        {{ $ligne['stock_debut_qte'] }}

                    </td>

                    <td>

                        {{ number_format($ligne['stock_debut_valeur'], 2, ',', ' ') }}

                    </td>

                    <td>

                        {{ $ligne['entree_qte'] }}

                    </td>

                    <td>

                        {{ number_format($ligne['entree_valeur'], 2, ',', ' ') }}

                    </td>

                    <td>

                        {{ $ligne['sortie_qte'] }}

                    </td>

                    <td>

                        {{ number_format($ligne['sortie_valeur'], 2, ',', ' ') }}

                    </td>

                    <td>

                        {{ $ligne['stock_fin_qte'] }}

                    </td>

                    <td>

                        {{ number_format($ligne['stock_fin_valeur'], 2, ',', ' ') }}

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

</body>

</html>
