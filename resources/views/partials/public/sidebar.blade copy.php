<div class="border-end bg-white table-dark table-skyblue" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom bg-light">
        <img src="{{ asset('assets/BSICBF-1.png') }}" alt="BSIC-BF" width="200" height="80">
    </div>

    <div class="list-group list-group-flush ">
        <a class="list-group-item list-group-item-action p-3" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('categories.index') }}">categories</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('departements.index') }}">Departements</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('services.index') }}">Services</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('agences.index') }}">Agences</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('articles.index') }}">Articles</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('commandes.index') }}">Commandes</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('fournisseurs.index') }}">Fournisseurs</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('demandes.index') }}">Demandes</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('users.index') }}">Users</a>
        <a class="list-group-item list-group-item-action p-3" href="{{ route('etats.index') }}">Etas</a>

    </div>
</div>
