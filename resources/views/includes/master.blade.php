<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>GESTOK-BSIC</title>
    <!-- Favicon-->
    @include('partials.public.styles')
    @stack('styles')
    @stack('scripts')
</head>

<body style="background-color: #edf4f4;">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar-->
        @include('partials.public.sidebar')

        <!-- Page content wrapper-->
        <div id="page-content-wrapper">
            <!-- Top navigation-->
            @include('partials.public.navbar')

            <!-- CONTENU PRINCIPAL -->
            <div class="container-fluid">

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success m-3">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </div>

            <!-- Page content-->

        </div>
    </div>
    <!-- Bootstrap core JS-->

    @include('partials.public.scripts')

    @stack('scripts')

    <footer class="text-white text-center py-3 mt-4" style="background-color: #5060f1;">

        <small>

            © {{ date('Y') }}

            GESTION DE STOCKS - BSIC BURKINA-SA

            | Tous droits réservés

        </small>

    </footer>

</body>

{{-- <footer class="row-footer">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <p style="padding:10px;"></p>
                <p align="center"><b>© Copyright 2026 BSIC BURKINA</b></p>
            </div>
        </div>
    </div>
</footer> --}}


</html>
