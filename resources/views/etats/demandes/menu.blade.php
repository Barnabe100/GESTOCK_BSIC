<div class="col-md-4 mb-4">

    <div class="dropdown">

        <button class="btn btn-success dropdown-toggle w-100" data-bs-toggle="dropdown">

            <i class="fas fa-file-alt"></i>

            Demandes

        </button>

        <ul class="dropdown-menu w-100">

            <!-- 🔥 PAR AGENCE -->
            <li>

                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#etatDemandesAgenceModal">

                    Etat des demandes par agence

                </a>

            </li>

            <!-- 🔥 SÉPARATEUR -->
            <li>
                <hr class="dropdown-divider">
            </li>

            <!-- 🔥 PAR DEPARTEMENT -->
            <li>

                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                    data-bs-target="#etatDemandesDepartementModal">

                    Etat des demandes par departement

                </a>

            </li>

            <!-- 🔥 SÉPARATEUR -->
            {{-- <li>
                <hr class="dropdown-divider">
            </li> --}}



            <!-- 🔥 PAR SERVICE -->
            {{-- <li class="dropdown-submenu">

                <a class="dropdown-item" href="#">

                    Par service

                </a>

                <ul class="dropdown-menu">

                    <!-- EXCEL -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.demandes.par_service.excel') }}">

                            <i class="fas fa-file-excel text-success"></i>

                            Excel

                        </a>

                    </li>

                    <!-- PDF -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.demandes.par_service.pdf') }}" target="_blank">

                            <i class="fas fa-file-pdf text-danger"></i>

                            PDF

                        </a>

                    </li>

                </ul>

            </li> --}}

            <!-- 🔥 SÉPARATEUR -->
            {{-- <li>
                <hr class="dropdown-divider">
            </li> --}}

            <!-- 🔥 PAR DEPARTEMENT -->
            {{-- <li class="dropdown-submenu">

                <a class="dropdown-item" href="#">

                    Par département

                </a>

                <ul class="dropdown-menu">

                    <!-- EXCEL -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.demandes.par_departement.excel') }}">

                            <i class="fas fa-file-excel text-success"></i>

                            Excel

                        </a>

                    </li>

                    <!-- PDF -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.demandes.par_departement.pdf') }}"
                            target="_blank">

                            <i class="fas fa-file-pdf text-danger"></i>

                            PDF

                        </a>

                    </li>

                </ul>

            </li> --}}

            <!-- 🔥 SÉPARATEUR -->
            {{-- <li>
                <hr class="dropdown-divider">
            </li> --}}

            <!-- 🔥 VALIDÉES -->
            {{-- <li class="dropdown-submenu">

                <a class="dropdown-item" href="#">

                    Validées

                </a>

                <ul class="dropdown-menu">

                    <!-- EXCEL -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.demandes.validees.excel') }}">

                            <i class="fas fa-file-excel text-success"></i>

                            Excel

                        </a>

                    </li>

                    <!-- PDF -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.demandes.validees.pdf') }}" target="_blank">

                            <i class="fas fa-file-pdf text-danger"></i>

                            PDF

                        </a>

                    </li>

                </ul>

            </li> --}}

        </ul>

    </div>

</div>
