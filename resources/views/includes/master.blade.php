<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Simple Sidebar - Start Bootstrap Template</title>
        <!-- Favicon-->
        @include('partials.public.styles')
        @stack('styles')
    </head>
    <body>
        <div class="d-flex" id="wrapper">
            <!-- Sidebar-->
            @include('partials.public.sidebar')

            <!-- Page content wrapper-->
            <div id="page-content-wrapper">
                <!-- Top navigation-->
                @include('partials.public.navbar')

                <!-- CONTENU PRINCIPAL -->
                <div class="container-fluid">

                    @if(session('success'))
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

    </body>
</html>
