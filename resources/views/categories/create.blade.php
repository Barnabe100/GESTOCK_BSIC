<form method="POST" action="{{ route('categories.store') }}">
    @csrf

    <input type="text" name="code" placeholder="Code">
    <input type="text" name="libelle" placeholder="Libelle">
    <textarea name="description"></textarea>

    <button>Enregistrer</button>
</form>