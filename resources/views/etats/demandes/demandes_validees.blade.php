<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>Demandes validées</title>

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
        État des demandes validées
    </h2>

    <table>

        <thead>

            <tr>

                <th>Objet</th>

                <th>Date</th>

                <th>Utilisateur</th>

                <th>Validation</th>

            </tr>

        </thead>

        <tbody>

            @foreach ($demandes as $demande)
                <tr>

                    <td>

                        {{ $demande->objet }}

                    </td>

                    <td>

                        {{ $demande->date }}

                    </td>

                    <td>

                        {{ $demande->user->prenom ?? '' }}

                        {{ $demande->user->name ?? '' }}

                    </td>

                    <td>

                        Validée

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

</body>

</html>
