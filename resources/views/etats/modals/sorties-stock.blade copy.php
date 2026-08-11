<!-- 🔥 POPUP ETAT DES SORTIES DE STOCK -->

<div class="modal fade modal-etat" id="etatSortiesStockModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">

                    Etat des sorties de stock

                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">

                </button>

            </div>

            <div class="modal-body">

                <div class="mb-3">

                    <label>

                        Date début

                    </label>

                    <input type="date" id="date_debut_sortie" max="{{ date('Y-m-d') }}" class="form-control">

                </div>

                <div class="mb-3">

                    <label>

                        Date fin

                    </label>

                    <input type="date" id="date_fin_sortie" max="{{ date('Y-m-d') }}" class="form-control">

                </div>

            </div>

            <div class="modal-footer justify-content-center gap-2">

                <!-- ANNULER -->

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    Annuler

                </button>

                <!-- EXCEL -->

                <form method="POST" action="{{ route('etats.sorties_stock.excel') }}">

                    @csrf

                    <input type="hidden" name="date_debut" id="excel_debut_sortie">

                    <input type="hidden" name="date_fin" id="excel_fin_sortie">

                    <button type="submit" class="btn btn-success" onclick="prepareExcelSortie()">

                        <i class="fas fa-file-excel"></i>

                        Excel

                    </button>

                </form>

                <!-- PDF -->

                <form method="POST" action="{{ route('etats.sorties_stock.pdf') }}" target="_blank">

                    @csrf

                    <input type="hidden" name="date_debut" id="pdf_debut_sortie">

                    <input type="hidden" name="date_fin" id="pdf_fin_sortie">

                    <button type="submit" class="btn btn-danger" onclick="preparePdfSortie()">

                        <i class="fas fa-file-pdf"></i>

                        PDF

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>