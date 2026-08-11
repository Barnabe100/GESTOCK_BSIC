<div class="modal fade modal-etat" id="etatDemandesAgenceModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">

                    Etat des demandes par agence

                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <form id="formDemandesAgence">

                    <div class="mb-3">

                        <label>Date début</label>

                        <input type="date" name="date_debut" max="{{ date('Y-m-d') }}" class="form-control" required>

                    </div>

                    <div class="mb-3">

                        <label>Date fin</label>

                        <input type="date" name="date_fin" max="{{ date('Y-m-d') }}" class="form-control" required>

                    </div>

                    <div class="mb-3">

                        <label>Agence</label>

                        <select id="demande_agence" name="agence_id" class="form-control select2">

                            <option value="">
                                Toutes les agences
                            </option>

                            @foreach ($agences as $agence)
                                <option value="{{ $agence->id }}">

                                    {{ $agence->nom }}

                                </option>
                            @endforeach

                        </select>

                    </div>

                </form>

            </div>

            <div class="modal-footer justify-content-center gap-2">

                <!-- ANNULER -->

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    Annuler

                </button>

                <button type="button" class="btn btn-success" onclick="exportDemandesAgenceExcel()">

                    Excel

                </button>

                <button type="button" class="btn btn-danger" onclick="exportDemandesAgencePdf()">

                    PDF

                </button>

            </div>

        </div>

    </div>

</div>


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

@push('scripts')
    <script>
        $('#etatDemandesAgenceModal').on('shown.bs.modal', function() {

            if ($('#demande_agence').hasClass('select2-hidden-accessible')) {

                $('#demande_agence').select2('destroy');
            }

            $('#demande_agence').select2({

                placeholder: 'Sélectionner ...',

                allowClear: true,

                width: '100%',

                dropdownParent: $('#etatDemandesAgenceModal')

            });

        });
    </script>
@endpush
