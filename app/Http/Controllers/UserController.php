<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Agence;
use App\Models\Departement;
use App\Models\Service;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'agence', 'departement', 'service'])->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $agences = Agence::all();
        $departements = Departement::all();
        $services = Service::all();

        return view('users.create', compact('agences', 'departements', 'services'));
    }

    public function store(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'agence_id' => $request->agence_id,
            'departement_id' => $request->departement_id,
            'service_id' => $request->service_id,
        ]);

        // 🔥 rôle par défaut
        $user->assignRole('Demandeur');

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec rôle Demandeur');
    }

    public function show($id)
    {
        $user = User::with(['roles', 'agence', 'departement', 'service'])->findOrFail($id);

        return view('users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        $roles = Role::all();
        $agences = Agence::all();
        $departements = Departement::all();
        $services = Service::all();

        return view('users.edit', compact('user', 'roles', 'agences', 'departements', 'services'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'agence_id' => $request->agence_id,
            'departement_id' => $request->departement_id,
            'service_id' => $request->service_id,
        ]);

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        // 🔥 multi rôles
        $user->syncRoles($request->roles ?? []);

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur modifié');
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return redirect()->route('users.index');
    }
}