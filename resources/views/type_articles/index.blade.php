@extends('includes.master')

@section('title', 'Liste des types d\'articles')

@section('content')

    <div class="container-fluid mt-4">

        <div class="card shadow-sm">

            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">

                <h5 class="mb-0">
                    Liste des types d'articles
                </h5>

                <div class="d-flex gap-2">

                    <!-- EXCEL -->
                    <a href="{{ route('type-articles.export.excel') }}" class="btn btn-success btn-sm">

                        <i class="fas fa-file-excel"></i>
                        Excel

                    </a>

                    <!-- CSV -->
                    <a href="{{ route('type-articles.export.csv') }}" class="btn btn-info btn-sm">

                        <i class="fas fa-file-csv"></i>
                        CSV

                    </a>

                    <a href="{{ route('type-articles.create') }}" class="btn btn-light btn-sm">

                        + Ajouter

                    </a>

                </div>

            </div>

            <!-- BODY -->
            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-striped datatable">

                        <thead style="background-color: #c3c3ee;">

                            <tr>

                                <th>#</th>
                                <th>Code</th>
                                <th>Libellé</th>
                                <th>Description</th>
                                <th width="180px">
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($typeArticles as $key => $typeArticle)
                                <tr>

                                    <td>{{ $key + 1 }}</td>

                                    <td>{{ $typeArticle->code }}</td>

                                    <td>{{ $typeArticle->libelle }}</td>

                                    <td>{{ $typeArticle->description }}</td>

                                    <td>

                                        <a href="{{ route('type-articles.show', $typeArticle->id) }}"
                                            class="btn btn-info btn-sm" title="Détail">

                                            <i class="fas fa-eye"></i>

                                        </a>

                                        <a href="{{ route('type-articles.edit', $typeArticle->id) }}"
                                            class="btn btn-warning btn-sm" title="Modifier">

                                            <i class="fas fa-edit"></i>

                                        </a>

                                        <form action="{{ route('type-articles.destroy', $typeArticle->id) }}" method="POST"
                                            style="display:inline-block">

                                            @csrf
                                            @method('DELETE')

                                            <button type="button" class="btn btn-danger btn-sm" title="Archiver"
                                                onclick="confirmDelete(this.form)">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection
