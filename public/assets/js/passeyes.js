function togglePassword(
    inputId,
    eyeIconId
) {

    let password =
        document.getElementById(inputId);

    let eyeIcon =
        document.getElementById(eyeIconId);

    if (
        password.type === 'password'
    ) {

        password.type = 'text';

        eyeIcon.classList.remove(
            'fa-eye'
        );

        eyeIcon.classList.add(
            'fa-eye-slash'
        );

    } else {

        password.type = 'password';

        eyeIcon.classList.remove(
            'fa-eye-slash'
        );

        eyeIcon.classList.add(
            'fa-eye'
        );

    }

}
