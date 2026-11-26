<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Suppression de son propre compte : refusée (lot 1, L1-3).
     *
     * Supprimer un utilisateur supprime en cascade ses demandes et leurs
     * lignes (clé étrangère demandes.user_id), donc l'historique des sorties
     * de stock. Les comptes sont gérés par les administrateurs.
     * Le contrôle du mot de passe est conservé à l'identique ; l'erreur est
     * renvoyée dans le sac « userDeletion » déjà prévu par la vue.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        return Redirect::back()->withErrors(
            ['password' => "La suppression de compte n'est pas autorisée. Contactez l'administrateur."],
            'userDeletion'
        );
    }
}
