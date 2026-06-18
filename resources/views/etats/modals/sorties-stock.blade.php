<!-- 🔥 POPUP ETAT DES SORTIES PAR ARTICLE -->

<div class="modal fade" id="etatSortiesStockModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">

                    Etat des sorties par article

                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">

                </button>

            </div>

            <div class="modal-body">

                <div class="mb-3">

                    <label>

                        Date début

                    </label>

                    <input type="date" id="date_debut_sortie" class="form-control">

                </div>

                <div class="mb-3">

                    <label>

                        Date fin

                    </label>

                    <input type="date" id="date_fin_sortie" class="form-control">

                </div>

                <div class="mb-3">

                    <label>

                        Article

                    </label>

                    <select id="article_sortie" class="form-control select2">

                        <option value="">

                            Tous les articles

                        </option>

                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}">

                                {{ $article->libelle }}

                            </option>
                        @endforeach

                    </select>

                </div>

            </div>

            <div class="modal-footer justify-content-center gap-2">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    Annuler

                </button>

                <!-- EXCEL -->

                <form method="POST" action="{{ route('etats.sorties_stock.excel') }}">

                    @csrf

                    <input type="hidden" name="date_debut" id="excel_debut_sortie_article">

                    <input type="hidden" name="date_fin" id="excel_fin_sortie_article">

                    <input type="hidden" name="article_id" id="excel_article_sortie">

                    <button type="submit" class="btn btn-success" onclick="prepareExcelSortieArticle()">

                        <i class="fas fa-file-excel"></i>

                        Excel

                    </button>

                </form>

                <!-- PDF -->

                <form method="GET" action="{{ route('etats.sorties_stock.pdf') }}" target="_blank">

                    {{-- @csrf --}}

                    <input type="hidden" name="date_debut" id="pdf_debut_sortie_article">

                    <input type="hidden" name="date_fin" id="pdf_fin_sortie_article">

                    <input type="hidden" name="article_id" id="pdf_article_sortie">

                    <button type="submit" class="btn btn-danger" onclick="preparePdfSortieArticle()">

                        <i class="fas fa-file-pdf"></i>

                        PDF

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


@push('scripts')
    <script>
        $('#etatSortiesStockModal').on('shown.bs.modal', function() {

            if ($('#article_sortie').hasClass('select2-hidden-accessible')) {

                $('#article_sortie').select2('destroy');
            }

            $('#article_sortie').select2({

                placeholder: 'Sélectionner ...',

                allowClear: true,

                width: '100%',

                dropdownParent: $('#etatSortiesStockModal')

            });

        });
    </script>
@endpush
