<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ForcePasswordController extends Controller
{
    public function show()
    {
        return view(
            'auth.force-password-change'
        );
    }

    public function update(Request $request)
    {
        $request->validate(
            [
                'password' => [
                    'required',
                    'confirmed',
                    'min:8'
                ]
            ],
            [
                'password.required' =>
                'Le mot de passe est obligatoire.',

                'password.confirmed' =>
                'Les mots de passe ne correspondent pas.',

                'password.min' =>
                'Le mot de passe doit contenir au moins 8 caractères.'
            ]
        );

        $user = Auth::user();

        $user->update([

            'password' => Hash::make(
                $request->password
            ),

            'force_password_change' => false

        ]);

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Mot de passe modifié avec succès.'
            );
    }
}
