@extends('includes.master')

@section('title', 'États')

@section('content')

<div class="container-fluid mt-4">

    <div class="card shadow-sm">

        <div class="card-header bg-primary text-white">

            <h5>

                Gestion des états

            </h5>

        </div>

        <div class="card-body">

            <div class="row">

                @include('etats.demandes.menu')

                @include('etats.articles.menu')

                @include('etats.commandes.menu')

            </div>

        </div>

    </div>

</div>




<!-- 🔥 POPUP ETAT STOCK -->

<div class="modal fade" id="etatStockModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <!-- HEADER -->

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">

                    Etat des stocks

                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">

                </button>

            </div>



            <!-- BODY -->

            <div class="modal-body">

                <div class="mb-3">

                    <label>

                        Date début

                    </label>

                    <input type="date" id="date_debut" class="form-control" required>

                </div>



                <div class="mb-3">

                    <label>

                        Date fin

                    </label>

                    <input type="date" id="date_fin" class="form-control" required>

                </div>

            </div>



            <!-- FOOTER -->

            <div class="modal-footer justify-content-center gap-2">

                <!-- ANNULER -->

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    <i class="fas fa-times"></i>

                    Annuler

                </button>



                <!-- EXCEL -->

                <form method="POST" action="{{ route('etats.stocks.excel') }}">

                    @csrf

                    <input type="hidden" name="date_debut" id="excel_date_debut">


                    <input type="hidden" name="date_fin" id="excel_date_fin">



                    <button type="submit" class="btn btn-success" onclick="prepareExcel()">

                        <i class="fas fa-file-excel"></i>

                        Excel

                    </button>

                </form>



                <!-- PDF -->

                <form method="POST" action="{{ route('etats.stocks.pdf') }}" target="_blank">

                    @csrf

                    <input type="hidden" name="date_debut" id="pdf_date_debut">


                    <input type="hidden" name="date_fin" id="pdf_date_fin">



                    <button type="submit" class="btn btn-danger" onclick="preparePdf()">

                        <i class="fas fa-file-pdf"></i>

                        PDF

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<!-- 🔥 POPUP ETAT STOCK VALORISÉ -->

<div class="modal fade" id="etatStockValoriseModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">

                    Etat stock valorisé

                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="mb-3">

                    <label>

                        Date début

                    </label>

                    <input type="date" id="date_debut_valorise" class="form-control">

                </div>

                <div class="mb-3">

                    <label>

                        Date fin

                    </label>

                    <input type="date" id="date_fin_valorise" class="form-control">

                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    Annuler

                </button>

                <!-- EXCEL -->

                <form method="POST" action="{{ route('etats.stocks_valorises.excel') }}">

                    @csrf

                    <input type="hidden" name="date_debut" id="excel_debut_valorise">

                    <input type="hidden" name="date_fin" id="excel_fin_valorise">

                    <button type="submit" class="btn btn-success" onclick="prepareExcelValorise()">

                        <i class="fas fa-file-excel"></i>

                        Excel

                    </button>

                </form>

                <!-- PDF -->

                <form method="POST" action="{{ route('etats.stocks_valorises.pdf') }}" target="_blank">

                    @csrf

                    <input type="hidden" name="date_debut" id="pdf_debut_valorise">

                    <input type="hidden" name="date_fin" id="pdf_fin_valorise">

                    <button type="submit" class="btn btn-danger" onclick="preparePdfValorise()">

                        <i class="fas fa-file-pdf"></i>

                        PDF

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<!-- 🔥 POPUP ETAT DES ENTREES DE STOCK -->

<div class="modal fade" id="etatEntreesStockModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">

                    Etat des entrées de stock

                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">

                </button>

            </div>

            <div class="modal-body">

                <div class="mb-3">

                    <label>

                        Date début

                    </label>

                    <input type="date" id="date_debut_entree" class="form-control">

                </div>

                <div class="mb-3">

                    <label>

                        Date fin

                    </label>

                    <input type="date" id="date_fin_entree" class="form-control">

                </div>

            </div>

            <div class="modal-footer justify-content-center gap-2">

                <!-- ANNULER -->

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    Annuler

                </button>

                <!-- EXCEL -->

                <form method="POST" action="{{ route('etats.entrees_stock.excel') }}">

                    @csrf

                    <input type="hidden" name="date_debut" id="excel_debut_entree">

                    <input type="hidden" name="date_fin" id="excel_fin_entree">

                    <button type="submit" class="btn btn-success" onclick="prepareExcelEntree()">

                        <i class="fas fa-file-excel"></i>

                        Excel

                    </button>

                </form>

                <!-- PDF -->

                <form method="POST" action="{{ route('etats.entrees_stock.pdf') }}" target="_blank">

                    @csrf

                    <input type="hidden" name="date_debut" id="pdf_debut_entree">

                    <input type="hidden" name="date_fin" id="pdf_fin_entree">

                    <button type="submit" class="btn btn-danger" onclick="preparePdfEntree()">

                        <i class="fas fa-file-pdf"></i>

                        PDF

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

</div>

@endsection

<script>
    function prepareExcel() {

        document.getElementById(
                'excel_date_debut'
            ).value =

            document.getElementById(
                'date_debut'
            ).value;



        document.getElementById(
                'excel_date_fin'
            ).value =

            document.getElementById(
                'date_fin'
            ).value;

    }



    function preparePdf() {

        document.getElementById(
                'pdf_date_debut'
            ).value =

            document.getElementById(
                'date_debut'
            ).value;



        document.getElementById(
                'pdf_date_fin'
            ).value =

            document.getElementById(
                'date_fin'
            ).value;

    }
</script>

<script>
    function prepareExcelValorise() {

        document.getElementById(
                'excel_debut_valorise'
            ).value =

            document.getElementById(
                'date_debut_valorise'
            ).value;


        document.getElementById(
                'excel_fin_valorise'
            ).value =

            document.getElementById(
                'date_fin_valorise'
            ).value;

    }



    function preparePdfValorise() {

        document.getElementById(
                'pdf_debut_valorise'
            ).value =

            document.getElementById(
                'date_debut_valorise'
            ).value;


        document.getElementById(
                'pdf_fin_valorise'
            ).value =

            document.getElementById(
                'date_fin_valorise'
            ).value;

    }
</script>

<script>
    function prepareExcelEntree() {

        document.getElementById(
                'excel_debut_entree'
            ).value =

            document.getElementById(
                'date_debut_entree'
            ).value;


        document.getElementById(
                'excel_fin_entree'
            ).value =

            document.getElementById(
                'date_fin_entree'
            ).value;

    }



    function preparePdfEntree() {

        document.getElementById(
                'pdf_debut_entree'
            ).value =

            document.getElementById(
                'date_debut_entree'
            ).value;


        document.getElementById(
                'pdf_fin_entree'
            ).value =

            document.getElementById(
                'date_fin_entree'
            ).value;

    }
</script>