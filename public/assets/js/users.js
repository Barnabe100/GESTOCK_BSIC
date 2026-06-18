$(document).ready(function () {

    $('.select2').select2({

        placeholder: 'Sélectionner ...',

        allowClear: true,

        width: '100%'

    });

    $('#agence_id').change(function () {

        let agenceId = $(this).val();

        $('#departement_id').html(
            '<option value="">Chargement...</option>'
        );

        $('#service_id').html(
            '<option value=""></option>'
        );

        if (agenceId) {

            $.get(

                '/departements/par-agence/' +
                agenceId,

                function (data) {

                    let options =
                        '<option value=""></option>';

                    data.forEach(function (dep) {

                        options +=

                            '<option value="' +
                            dep.id +
                            '">' +
                            dep.nom +
                            '</option>';

                    });

                    $('#departement_id')
                        .html(options)
                        .trigger('change');

                }

            );

        }

    });

    $('#departement_id').change(function () {

        let departementId =
            $(this).val();

        $('#service_id').html(
            '<option value="">Chargement...</option>'
        );

        if (departementId) {

            $.get(

                '/services/par-departement/' +
                departementId,

                function (data) {

                    let options =
                        '<option value=""></option>';

                    data.forEach(function (service) {

                        options +=

                            '<option value="' +
                            service.id +
                            '">' +
                            service.nom +
                            '</option>';

                    });

                    $('#service_id')
                        .html(options);

                }

            );

        }

    });

});