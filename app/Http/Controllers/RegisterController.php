<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Agence;
use App\Models\Departement;
use App\Models\Service;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        $agences = Agence::all();
        $departements = Departement::all();
        $services = Service::all();

        return view('auth.register', compact('agences', 'departements', 'services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);

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

        // 🔥 connexion automatique (option)
        Auth::login($user);

        return redirect()->route('accueil');
    }
}