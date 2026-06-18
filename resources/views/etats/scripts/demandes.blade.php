<script>
    function exportDemandesAgenceExcel() {
        let params = new URLSearchParams(
            new FormData(
                document.getElementById(
                    'formDemandesAgence'
                )
            )
        );

        window.location =
            "{{ route('etats.demandes.par_agence.excel') }}" +
            '?' +
            params.toString();
    }

    function exportDemandesAgencePdf() {
        let params = new URLSearchParams(
            new FormData(
                document.getElementById(
                    'formDemandesAgence'
                )
            )
        );

        window.open(
            "{{ route('etats.demandes.par_agence.pdf') }}" +
            '?' +
            params.toString(),
            '_blank'
        );
    }
</script>
