<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // Socle des droits : même politique que le changement obligatoire
        // (ForcePasswordController) et que le profil (UserController), avec
        // les messages existants ; un changement conforme lève l'obligation.
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'min:12',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ], [
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.regex' => 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        return back()->with('status', 'password-updated');
    }
}
