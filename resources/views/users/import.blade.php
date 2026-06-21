@extends('includes.master')

@section('title', 'Import utilisateurs')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card">

            @if ($errors->any())

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif

            <div class="card-header bg-primary text-white">

                Import des utilisateurs

            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('users.import') }}" enctype="multipart/form-data">

                    @csrf

                    <div class="mb-3">

                        <label>

                            Fichier Excel

                        </label>

                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>

                    </div>

                    <button type="submit" class="btn btn-success">
                        Importer
                    </button>

                </form>

            </div>

        </div>

    </div>

@endsection
