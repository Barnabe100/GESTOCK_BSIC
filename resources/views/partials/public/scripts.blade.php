<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<script src="{{ asset('assets/js/scripts.js') }}"></script>

<!-- DATATABLE-->
<script src="{{ asset('assets/js/jquery-3.7.0.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/dataTables.bootstrap5.min.js') }}"></script>

<!-- FONT AWESOME-->
<script src="{{ asset('assets/webfonts/all.min.js') }}"></script>
<script src="{{ asset('assets/webfonts/brands.min.js') }}"></script>
<script src="{{ asset('assets/webfonts/conflict-detection.min.js') }}"></script>
<script src="{{ asset('assets/webfonts/fontawesome.min.js') }}"></script>
<script src="{{ asset('assets/webfonts/regular.min.js') }}"></script>
<script src="{{ asset('assets/webfonts/solid.min.js') }}"></script>
<!-- FONT AWESOME-->
<script>
    $(document).ready(function() {

        $('.datatable').DataTable({
            responsive: true,
            language: {
                url: "/assets/datatables/fr-FR.json"
            }
        });

    });
</script>
<!-- Validation demande-->

<script src="{{ asset('assets/js/sweetalert2@11.js') }}"></script>
<!-- COnfirmation-->
{{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
<!-- selection sur un champs-->
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<!-- graphiques-->
{{-- <script src="{{ asset('assets/js/chart.js') }}></script> --}}
<script>
    function confirmDelete(form) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>

<script>
    function confirmAction(form, message) {
        Swal.fire({
            title: 'Confirmation',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, confirmer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>



<script>
    document.querySelectorAll('.dropdown-submenu .dropdown-toggle')
        .forEach(function(element) {

            element.addEventListener('click', function(e) {

                e.preventDefault();
                e.stopPropagation();

                this.nextElementSibling.classList.toggle('show');
            });
        });
</script>

<script>
    $('#departement_id').change(function() {

        let departementId = $(this).val();

        $('#service_id').html(
            '<option value="">Chargement...</option>'
        );

        $.get(

            '/departements/' +
            departementId +
            '/services',

            function(data) {

                let options =

                    '<option value="">Choisir un service</option>';

                data.forEach(function(service) {

                    options +=

                        '<option value="' +
                        service.id +
                        '">' +
                        service.libelle +
                        '</option>';

                });

                $('#service_id').html(
                    options
                );

            }

        );

    });
</script>
