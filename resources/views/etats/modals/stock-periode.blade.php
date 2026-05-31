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
