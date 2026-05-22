<div class="col-md-4 mb-4">

    <div class="dropdown">

        <!-- MODULE -->
        <button class="btn btn-primary dropdown-toggle w-100" data-bs-toggle="dropdown">

            <i class="fas fa-box"></i>

            Articles

        </button>

        <!-- ETATS -->
        <ul class="dropdown-menu w-100">

            <!-- 🔥 STOCK PAR ARTICLE -->
            <li class="dropdown-submenu">

                <a class="dropdown-item" href="#">

                    Stock par article

                </a>

                <ul class="dropdown-menu">

                    <!-- EXCEL -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.articles.stock_par_article.excel') }}">

                            <i class="fas fa-file-excel text-success"></i>

                            Excel

                        </a>

                    </li>

                    <!-- PDF -->
                    <li>

                        <a class="dropdown-item" href="{{ route('etats.articles.stock_par_article.pdf') }}"
                            target="_blank">

                            <i class="fas fa-file-pdf text-danger"></i>

                            PDF

                        </a>

                    </li>

                </ul>

            </li>

        </ul>

    </div>

</div>
