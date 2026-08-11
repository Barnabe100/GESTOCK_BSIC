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

<script>
    function prepareExcelSortie() {

        document.getElementById(
                'excel_debut_sortie'
            ).value =

            document.getElementById(
                'date_debut_sortie'
            ).value;


        document.getElementById(
                'excel_fin_sortie'
            ).value =

            document.getElementById(
                'date_fin_sortie'
            ).value;

    }



    function preparePdfSortie() {

        document.getElementById(
                'pdf_debut_sortie'
            ).value =

            document.getElementById(
                'date_debut_sortie'
            ).value;


        document.getElementById(
                'pdf_fin_sortie'
            ).value =

            document.getElementById(
                'date_fin_sortie'
            ).value;

    }
</script>

<script>
    function prepareExcelSortieArticle() {

        document.getElementById(
                'excel_debut_sortie_article'
            ).value =

            document.getElementById(
                'date_debut_sortie'
            ).value;

        document.getElementById(
                'excel_fin_sortie_article'
            ).value =

            document.getElementById(
                'date_fin_sortie'
            ).value;

        document.getElementById(
                'excel_article_sortie'
            ).value =

            document.getElementById(
                'article_sortie'
            ).value;
    }

    function preparePdfSortieArticle() {

        document.getElementById(
                'pdf_debut_sortie_article'
            ).value =

            document.getElementById(
                'date_debut_sortie'
            ).value;

        document.getElementById(
                'pdf_fin_sortie_article'
            ).value =

            document.getElementById(
                'date_fin_sortie'
            ).value;

        document.getElementById(
                'pdf_article_sortie'
            ).value =

            document.getElementById(
                'article_sortie'
            ).value;
    }
</script>

<script>
    function prepareExcelStockFinal() {

        document.getElementById(
                'excel_debut_stock_final'
            ).value =

            document.getElementById(
                'date_debut_stock_final'
            ).value;

        document.getElementById(
                'excel_fin_stock_final'
            ).value =

            document.getElementById(
                'date_fin_stock_final'
            ).value;

    }

    function preparePdfStockFinal() {

        document.getElementById(
                'pdf_debut_stock_final'
            ).value =

            document.getElementById(
                'date_debut_stock_final'
            ).value;

        document.getElementById(
                'pdf_fin_stock_final'
            ).value =

            document.getElementById(
                'date_fin_stock_final'
            ).value;

    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.modal-etat').forEach(function(modal) {

            modal.addEventListener('hidden.bs.modal', function() {

                modal.querySelectorAll('form').forEach(function(form) {
                    form.reset();
                });

                modal.querySelectorAll('input').forEach(function(input) {
                    input.value = '';
                });

                modal.querySelectorAll('select').forEach(function(select) {
                    select.selectedIndex = 0;
                });

            });

        });

    });
</script>


{{-- <script>
    function prepareExcelSortieArticle() {
        document.getElementById(
                'excel_debut_sortie_article'
            ).value =
            document.getElementById(
                'date_debut_sortie_article'
            ).value;

        document.getElementById(
                'excel_fin_sortie_article'
            ).value =
            document.getElementById(
                'date_fin_sortie_article'
            ).value;

        document.getElementById(
                'excel_article_sortie'
            ).value =
            document.getElementById(
                'article_sortie'
            ).value;
    }

    function preparePdfSortieArticle() {
        document.getElementById(
                'pdf_debut_sortie_article'
            ).value =
            document.getElementById(
                'date_debut_sortie_article'
            ).value;

        document.getElementById(
                'pdf_fin_sortie_article'
            ).value =
            document.getElementById(
                'date_fin_sortie_article'
            ).value;

        document.getElementById(
                'pdf_article_sortie'
            ).value =
            document.getElementById(
                'article_sortie'
            ).value;
    }
</script> --}}
