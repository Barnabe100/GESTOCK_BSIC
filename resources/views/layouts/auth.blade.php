<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>

        @yield('title', 'Connexion')

    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">



    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">

</head>

<body style="background-color: #9fd3fb;">

    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">

        <div class="row shadow rounded-4 overflow-hidden bg-white w-75">

            <!-- 🔥 PARTIE GAUCHE -->
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-center text-white p-5"
                style="background-color: #087ad1;">

                <!-- LOGO -->
                <img src="{{ asset('assets/bsic.png') }}" alt="Logo" class="img-fluid mb-4" width="210">

                <!-- TITRE -->
                <div class="text-center">

                    <h2 class="fw-bold">

                        GESTION DE STOCKS

                    </h2>

                    <p class="mt-3 fs-5">

                        Faites vos demandes de consommables

                    </p>

                </div>

            </div>

            <!-- 🔥 PARTIE DROITE -->
            <div class="col-md-6 d-flex align-items-center p-5">

                <div class="w-100">

                    @yield('content')

                </div>

            </div>

        </div>

    </div>

    <script src="{{ asset('assets/js/scripts.js') }}"></script>


    <script>
        function togglePassword() {
            let password =
                document.getElementById('password');

            let eyeIcon =
                document.getElementById('eyeIcon');

            if (password.type === 'password') {
                password.type = 'text';

                eyeIcon.classList.remove('fa-eye');

                eyeIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';

                eyeIcon.classList.remove('fa-eye-slash');

                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>
