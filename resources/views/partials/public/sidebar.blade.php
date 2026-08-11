<div class="border-end bg-info-subtle" id="sidebar-wrapper" style="background-color: #d1dce6;">

    <div class="sidebar-heading border-bottom bg-info-subtle">

        <img src="{{ asset('assets/BSICBF-1.png') }}" alt="BSIC-BF" width="180" height="80">

    </div>

    {{-- <div class="list-group list-group-flush"> --}}
    <div class=" list-group-flush">

        <a class="list-group-item list-group-item-action p-3 bg-info-subtle " href="{{ route('dashboard') }}">

            Dashboard

        </a>


        @can('gerer-departement')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('departements.index') }}">

                Departements

            </a>
        @endcan
        @can('gerer-service')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('services.index') }}">

                Services

            </a>
        @endcan
        @can('gerer-agence')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('agences.index') }}">

                Agences

            </a>
        @endcan
        @can('gerer-utilisateurs')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('users.index') }}">

                Users

            </a>
        @endcan


        <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('mes.demandes') }}">

            Mes demandes

        </a>

        {{-- Demandes des collègues --}}
        {{-- @if (!auth()->user()->hasAnyRole(['Gestionnaire', 'ValidateurDemande', 'ValidateurCommande'])) --}}
        <a class="list-group-item list-group-item-action p-3 bg-info-subtle"
            href="{{ route('demandes.organisation') }}">

            {{-- {{ auth()->user()->estAuSiege() ? 'Demandes du département' : 'Demandes de l\'agence' }} --}}

            {{ auth()->check() ? (auth()->user()->estAuSiege() ? 'Demandes du département' : 'Demandes de l\'agence') : '' }}
        </a>
        {{-- @endif --}}

        @can('gerer-stock')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('categories.index') }}">

                Categories

            </a>

            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('type-articles.index') }}">

                Types d'articles

            </a>


            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('articles.index') }}">

                Articles

            </a>
        @endcan
        @if (auth()->check() &&
                auth()->user()->hasAnyRole(['ValidateurCommande', 'Gestionnaire', 'SuperAdmin']))
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('commandes.index') }}">

                Commandes

            </a>
        @endif
        @if (auth()->check() &&
                auth()->user()->hasAnyRole(['SuperAdmin', 'Gestionnaire']))
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle"
                href="{{ route('fournisseurs.index') }}">

                Fournisseurs

            </a>
        @endif


        @if (auth()->check() &&
                auth()->user()->hasAnyRole(['Gestionnaire']))
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle"
                href="{{ route('demandes.gestionnaire') }}">

                Demandes

            </a>
        @endif

        @role('ValidateurDemande')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle"
                href="{{ route('demandes.validation.finale.index') }}">

                Demandes

            </a>
        @endrole



        @can('gerer-stock')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('etats.index') }}">

                États

            </a>
        @endcan

        @role('Superieur')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('demandes.superieur') }}">

                Demandes

            </a>
        @endrole

        @role('Gestionnaire')
            <a class="list-group-item list-group-item-action p-3 bg-info-subtle" href="{{ route('mouvements.index') }}">

                Mouvements stock

            </a>
        @endrole

    </div>

</div>
