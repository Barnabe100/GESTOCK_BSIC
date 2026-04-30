<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Connexion')</title>

    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container">
    <div class="row justify-content-center mt-5">

        <div class="col-md-5">

            <div class="card shadow">

                <div class="card-body">

                    @yield('content')

                </div>

            </div>

        </div>

    </div>
</div>

<script src="{{ asset('assets/js/scripts.js') }}"></script>

</body>
</html>