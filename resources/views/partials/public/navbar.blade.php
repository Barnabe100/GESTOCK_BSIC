<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container-fluid">

        <button class="btn btn-primary" id="sidebarToggle">
            <i class="fas fa-align-justify"></i>  
        </button>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                @guest
                <li class="nav-item"><a class="nav-link" href="{{route('login')}}">Se connecter</a></li>
                @endguest
                @auth
                <li class="nav-item">
                    <a id="color-nav" class="nav-link" href="{{ route('logout') }}"
                        onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                        {{ __('Se deconnecter') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                    </form>
                </li>
                @endauth
            </ul>
        </div>

    </div>
</nav>
