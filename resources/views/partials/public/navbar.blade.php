<nav class="navbar navbar-expand-lg navbar-light border-bottom" style="background-color: #6993e0;">
    <div class="container-fluid">

        <button class="btn btn-primary" id="sidebarToggle">
            <i class="fas fa-align-justify"></i>
        </button>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                {{-- <li class="nav-item"><a class="nav-link" href="#">Home</a></li> --}}
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Se connecter</a></li>
                @endguest
                @auth


                    <li class="nav-item dropdown">

                        <!-- TOGGLE -->
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">

                            {{ Auth::user()->prenom }} {{ Auth::user()->name }}


                        </a>

                        <!-- MENU -->
                        <ul class="dropdown-menu dropdown-menu-end">

                            <!-- MON PROFIL -->
                            <li>

                                <a class="dropdown-item" href="{{ route('mon.profil') }}">

                                    <i class="fas fa-user me-2"></i>

                                    Mon profil

                                </a>

                            </li>



                            <!-- SEPARATEUR -->
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <!-- LOGOUT -->
                            <li>

                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">

                                    <i class="fas fa-sign-out-alt me-2"></i>

                                    Se déconnecter

                                </a>

                            </li>

                        </ul>

                        <!-- FORM LOGOUT -->
                        <form id="logout-form" action="{{ route('logout') }}" method="POST">

                            @csrf

                        </form>

                    </li>

                    <li class="nav-item">

                    </li>
                @endauth
            </ul>
        </div>

    </div>
</nav>
