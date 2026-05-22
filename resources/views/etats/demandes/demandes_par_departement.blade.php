<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>

        Demandes par département

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

            text-align: center;

        }
    </style>

</head>

<body>

    <h2>

        État des demandes par département

    </h2>

    <table>

        <thead>

            <tr>

                <th>Objet</th>

                <th>Date</th>

                <th>Utilisateur</th>

                <th>Département</th>

                <th>Validation</th>

            </tr>

        </thead>

        <tbody>

            @foreach ($demandes as $demande)
                <tr>

                    <!-- OBJET -->
                    <td>

                        {{ $demande->objet }}

                    </td>

                    <!-- DATE -->
                    <td>

                        {{ $demande->date }}

                    </td>

                    <!-- UTILISATEUR -->
                    <td>

                        {{ $demande->user->prenom ?? '' }}

                        {{ $demande->user->name ?? '' }}

                    </td>

                    <!-- DEPARTEMENT -->
                    <td>

                        {{ $demande->user->departement->nom ?? '' }}

                    </td>

                    <!-- VALIDATION -->
                    <td>

                        @if ($demande->validation)
                            Validée
                        @else
                            Non validée
                        @endif

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

</body>

</html>
