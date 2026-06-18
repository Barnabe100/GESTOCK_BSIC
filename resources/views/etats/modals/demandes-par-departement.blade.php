<div class="modal fade" id="etatDemandesDepartementModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">

                    Etat des demandes par département

                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <form id="formDemandesDepartement">

                    <div class="mb-3">

                        <label>Date début</label>

                        <input type="date" name="date_debut" class="form-control" required>

                    </div>

                    <div class="mb-3">

                        <label>Date fin</label>

                        <input type="date" name="date_fin" class="form-control" required>

                    </div>

                    <div class="mb-3">

                        <label>Département</label>

                        <select id="demande_departement" name="departement_id" class="form-control select2">

                            <option value="">
                                Tous les départements
                            </option>

                            @foreach ($departements as $departement)
                                <option value="{{ $departement->id }}">

                                    {{ $departement->nom }}

                                </option>
                            @endforeach

                        </select>

                    </div>

                </form>

            </div>

            <div class="modal-footer justify-content-center gap-2">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                    Annuler

                </button>

                <button type="button" class="btn btn-success" onclick="exportDemandesDepartementExcel()">

                    Excel

                </button>

                <button type="button" class="btn btn-danger" onclick="exportDemandesDepartementPdf()">

                    PDF

                </button>

            </div>

        </div>

    </div>

</div>

<script>
    function exportDemandesDepartementExcel() {

        let params = new URLSearchParams(

            new FormData(

                document.getElementById(
                    'formDemandesDepartement'
                )

            )

        );

        window.location =

            "{{ route('etats.demandes.par_departement.excel') }}"

            +

            '?'

            +

            params.toString();

    }

    function exportDemandesDepartementPdf() {

        let params = new URLSearchParams(

            new FormData(

                document.getElementById(
                    'formDemandesDepartement'
                )

            )

        );

        window.open(

            "{{ route('etats.demandes.par_departement.pdf') }}"

            +

            '?'

            +

            params.toString(),

            '_blank'

        );

    }
</script>

@push('scripts')
    <script>
        $('#etatDemandesDepartementModal').on(

            'shown.bs.modal',

            function() {

                if (

                    $('#demande_departement')

                    .hasClass(
                        'select2-hidden-accessible'
                    )

                ) {

                    $('#demande_departement')
                        .select2('destroy');

                }

                $('#demande_departement').select2({

                    placeholder: 'Sélectionner ...',

                    allowClear: true,

                    width: '100%',

                    dropdownParent: $('#etatDemandesDepartementModal')

                });

            }

        );
    </script>
@endpush
