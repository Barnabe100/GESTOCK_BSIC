<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>

        Demandes par service

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

        État des demandes par service

    </h2>

    <table>

        <thead>

            <tr>

                <th>Objet</th>

                <th>Date</th>

                <th>Utilisateur</th>

                <th>Service</th>

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

                        {{ $demande->user->service->nom ?? '' }}

                    </td>

                    <td>

                        {{ $demande->validation ? 'Validée' : 'Non validée' }}

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

</body>

</html>
