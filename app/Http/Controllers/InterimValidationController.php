<?php

namespace App\Http\Controllers;

use App\Models\InterimValidation;
use App\Models\User;
use App\Organisation\RefusOrganisation;
use App\Services\GestionInterims;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Q3 (§4.3) : désignation et révocation des remplaçants temporaires de
 * validation supérieure. Réservé à la gestion des comptes (capacité
 * gerer-utilisateurs, vérifiée par le middleware des routes).
 */
class InterimValidationController extends Controller
{
    public function index()
    {
        return view('users.interims', [
            'interims' => InterimValidation::with(['remplacant', 'auteur'])->latest('id')->get(),
            'entites' => GestionInterims::entitesDisponibles(),
            'remplacants' => User::with(['agence', 'departement'])->where('superieur', 1)->whereNull('desactive_le')
                ->where('bloque', false)->role('Superieur')->orderBy('name')->get(),
            'aSignaler' => GestionInterims::entitesAvecUnSeulValidateur(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'entite' => ['required', Rule::in(array_keys(GestionInterims::entitesDisponibles()))],
            'user_id' => 'required|integer|exists:users,id',
            'debut_le' => 'required|date',
            'fin_le' => 'required|date|after_or_equal:debut_le',
            'motif' => 'nullable|string|max:255',
        ], [
            'entite.required' => 'Choisissez l\'entité concernée.',
            'entite.in' => 'Entité invalide.',
            'user_id.required' => 'Choisissez le remplaçant.',
            'debut_le.required' => 'La date de début est obligatoire.',
            'fin_le.required' => 'La date de fin est obligatoire.',
            'fin_le.after_or_equal' => 'La date de fin doit suivre la date de début.',
        ]);

        [$type, $id] = explode(':', $request->entite);

        try {
            app(GestionInterims::class)->designer([$type, (int) $id], User::findOrFail($request->user_id),
                $request->debut_le, $request->fin_le, $request->motif, auth()->user());
        } catch (RefusOrganisation $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('interims.index')->with('success', 'Remplaçant temporaire désigné.');
    }

    public function revoquer($id)
    {
        app(GestionInterims::class)->revoquer(InterimValidation::findOrFail($id), auth()->user());

        return redirect()->route('interims.index')->with('success', 'Intérim révoqué.');
    }
}
