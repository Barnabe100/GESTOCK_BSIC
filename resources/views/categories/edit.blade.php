<form method="POST" action="{{ route('categories.update', $categorie->id) }}">
    @csrf
    @method('PUT')

    <input type="text" name="code" value="{{ $categorie->code }}">
    <input type="text" name="libelle" value="{{ $categorie->libelle }}">

    <button>Modifier</button>
</form>