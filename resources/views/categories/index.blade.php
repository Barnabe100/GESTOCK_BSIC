<a href="{{ route('categories.create') }}">Ajouter</a>

@foreach($categories as $cat)
    <p>{{ $cat->libelle }}</p>

    <a href="{{ route('categories.edit', $cat->id) }}">Edit</a>

    <form method="POST" action="{{ route('categories.destroy', $cat->id) }}">
        @csrf
        @method('DELETE')
        <button>Supprimer</button>
    </form>
@endforeach