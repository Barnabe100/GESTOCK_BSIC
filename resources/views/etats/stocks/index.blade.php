@extends('includes.master')

@section('title', 'Etat stock')

@section('content')

    <div class="container mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">

                <h5>

                    Etat des stocks

                </h5>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <label>

                            Date début

                        </label>

                        <input type="date" id="date_debut" class="form-control" required>

                    </div>


                    <div class="col-md-4">

                        <label>

                            Date fin

                        </label>

                        <input type="date" id="date_fin" class="form-control" required>

                    </div>

                </div>


                <div class="mt-4">

                    <!-- EXCEL -->

                    <form method="POST" action="{{ route('etats.stocks.excel') }}" class="d-inline">

                        @csrf

                        <input type="hidden" name="date_debut" id="excel_date_debut">

                        <input type="hidden" name="date_fin" id="excel_date_fin">

                        <button type="submit" class="btn btn-success">

                            <i class="fas fa-file-excel"></i>

                            Export Excel

                        </button>

                    </form>



                    <!-- PDF -->

                    <form method="POST" action="{{ route('etats.stocks.pdf') }}" class="d-inline">

                        @csrf

                        <input type="hidden" name="date_debut" id="pdf_date_debut">

                        <input type="hidden" name="date_fin" id="pdf_date_fin">

                        <button type="submit" class="btn btn-danger">

                            <i class="fas fa-file-pdf"></i>

                            Export PDF

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>



    <script>
        document.addEventListener(

            'DOMContentLoaded',

            function() {

                function syncDates() {

                    let debut =

                        document.getElementById(
                            'date_debut'
                        ).value;


                    let fin =

                        document.getElementById(
                            'date_fin'
                        ).value;



                    document.getElementById(
                        'excel_date_debut'
                    ).value = debut;

                    document.getElementById(
                        'excel_date_fin'
                    ).value = fin;



                    document.getElementById(
                        'pdf_date_debut'
                    ).value = debut;

                    document.getElementById(
                        'pdf_date_fin'
                    ).value = fin;

                }



                document.getElementById(
                    'date_debut'
                ).addEventListener(
                    'change',
                    syncDates
                );


                document.getElementById(
                    'date_fin'
                ).addEventListener(
                    'change',
                    syncDates
                );

            });
    </script>

@endsection
