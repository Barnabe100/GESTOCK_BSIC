<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>

        Etat stock

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

            padding: 6px;

            text-align: center;

        }


        th {

            background: #ddd;

        }


        h3 {

            margin: 0;

        }


        .header-table {

            width: 100%;

            margin-bottom: 20px;

            border: none;

        }


        .header-table td {

            border: none;

        }


        .title-zone {

            text-align: center;

        }
    </style>

</head>

<body>


    <!-- 🔥 ENTETE -->

    <table class="header-table">

        <tr>

            <!-- LOGO -->

            <td width="20%">

                <img src="{{ public_path('assets/BSICBF-1.png') }}" width="100">
                <!-- LOGO -->

            </td>



            <!-- TITRE -->

            @php
                use Carbon\Carbon;
            @endphp

            <td width="80%" class="title-zone">


                <h3>

                    ETAT DES STOCKS
                    EN QUANTITE

                </h3>

                <br>

                <strong>
                    DU
                    {{ Carbon::parse($dateDebut)->format('d/m/Y') }}
                    AU
                    {{ Carbon::parse($dateFin)->format('d/m/Y') }}
                </strong>

            </td>

        </tr>

    </table>



    <!-- 🔥 TABLEAU -->

    <table>

        <thead>

            <tr>

                {{-- <th>

                    DATE

                </th> --}}

                <th>

                    INTITULE ARTICLE

                </th>

                <th>

                    STOCK EN DEBUT
                    DE PERIODE

                </th>

                <th>

                    ENTREE DE LA
                    PERIODE

                </th>

                <th>

                    SORTIE DE LA
                    PERIODE

                </th>

                <th>

                    STOCK EN FIN
                    DE PERIODE

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

                        {{ $ligne['stock_debut'] }}

                    </td>

                    <td>

                        {{ $ligne['entrees'] }}

                    </td>

                    <td>

                        {{ $ligne['sorties'] }}

                    </td>

                    <td>

                        {{ $ligne['stock_fin'] }}

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

</body>

</html>
