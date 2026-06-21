@extends('layouts.auth')

@section('title', 'Changement de mot de passe')

@section('content')

    <h5 class="text-center mb-4">

        Changement obligatoire du mot de passe

    </h5>

    <div class="alert alert-warning">

        Pour des raisons de sécurité, vous devez définir un nouveau mot de passe avant d'accéder à l'application.

    </div>

    @if ($errors->any())
        <div class="alert alert-danger">

            {{ $errors->first() }}

        </div>
    @endif

    <form method="POST" action="{{ route('force.password.update') }}">

        @csrf

        <!-- Nouveau mot de passe -->
        <div class="mb-3">

            <label>Nouveau mot de passe</label>

            <div class="input-group">

                <input type="password" name="password" id="password" class="form-control" required>

                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', 'eyeIcon1')">

                    <i class="fas fa-eye" id="eyeIcon1"></i>

                </button>

            </div>

        </div>

        <!-- Confirmation -->
        <div class="mb-3">

            <label>Confirmation du mot de passe</label>

            <div class="input-group">

                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                    required>

                <button type="button" class="btn btn-outline-secondary"
                    onclick="togglePassword('password_confirmation', 'eyeIcon2')">

                    <i class="fas fa-eye" id="eyeIcon2"></i>

                </button>

            </div>

        </div>

        <button class="btn btn-primary w-100 mt-4">

            Modifier le mot de passe

        </button>

    </form>

@endsection

@push('scripts')
    <script>
        function togglePassword(inputId, iconId) {
            const password =
                document.getElementById(inputId);

            const icon =
                document.getElementById(iconId);

            if (password.type === 'password') {

                password.type = 'text';

                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');

            } else {

                password.type = 'password';

                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');

            }
        }
    </script>
@endpush
