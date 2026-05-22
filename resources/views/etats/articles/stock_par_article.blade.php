<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>

        Stock par article

    </title>

    <style>
        body {

            font-family: DejaVu Sans;
            font-size: 12px;

        }

        table {

            width: 100%;
            border-collapse: collapse;

        }

        table,
        th,
        td {

            border: 1px solid black;

        }

        th {

            background: #eeeeee;

        }

        th,
        td {

            padding: 8px;

        }

        h2 {

            margin-bottom: 20px;

        }
    </style>

</head>

<body>

    <h2>

        État du stock par article

    </h2>

    <table>

        <thead>

            <tr>

                <th>Code</th>

                <th>Désignation</th>

                <th>Catégorie</th>

                <th>Stock disponible</th>

                <th>Stock sécurité</th>

                <th>État stock</th>

            </tr>

        </thead>

        <tbody>

            @foreach ($articles as $article)
                <tr>

                    <td>

                        {{ $article->code }}

                    </td>

                    <td>

                        {{ $article->designation }}

                    </td>

                    <td>

                        {{ $article->categorie->libelle ?? '' }}

                    </td>

                    <td>

                        {{ $article->stock_disponible }}

                    </td>

                    <td>

                        {{ $article->stock_securite }}

                    </td>

                    <td>

                        @if ($article->stock_disponible <= $article->stock_securite)
                            Critique
                        @else
                            Disponible
                        @endif

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

</body>

</html>
