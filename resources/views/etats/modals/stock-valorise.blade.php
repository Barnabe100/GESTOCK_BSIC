<!-- 🔥 POPUP ETAT STOCK VALORISÉ -->

<div class="modal fade modal-etat" id="etatStockValoriseModal" tabindex="-1">

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

                    <input type="date" id="date_debut_valorise" max="{{ date('Y-m-d') }}" class="form-control">

                </div>

                <div class="mb-3">

                    <label>

                        Date fin

                    </label>

                    <input type="date" id="date_fin_valorise" max="{{ date('Y-m-d') }}" class="form-control">

                </div>

            </div>

            <div class="modal-footer justify-content-center gap-2">

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

                <form method="GET" action="{{ route('etats.stocks_valorises.pdf') }}" target="_blank">

                    {{-- @csrf --}}

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
