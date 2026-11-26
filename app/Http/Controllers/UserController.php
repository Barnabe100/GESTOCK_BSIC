<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Agence;
use App\Models\Departement;
use App\Models\Service;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use App\Imports\ImportInvalide;
use App\Services\DesignationSuperieur;
use App\Autorisation\CycleDeVieComptes;
use App\Autorisation\JournalRoles;
use App\Organisation\RefusOrganisation;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::with(['roles', 'agence', 'departement', 'service'])
            ->latest()
            ->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        $agences = Agence::all();
        $departements = Departement::all();
        $services = Service::all();
        $superieursRemplacables = $this->superieursEnFonction();

        return view('users.create', compact('agences', 'departements', 'services', 'superieursRemplacables'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        // Décision validée : désigner un supérieur dans une entité qui en a
        // déjà un en fait un remplaçant ; le précédent perd sa fonction, avec
        // historique (DesignationSuperieur). Le refus « … possède déjà un
        // directeur » ne s'applique donc plus.

        $request->validate(
            [
                'name' => 'required',
                'prenom' => 'required',
                'email' => 'required',

                'password' => [
                    'required',
                    'confirmed',
                    'min:8'
                ],

                //'departement_id' => 'required',
                //'service_id' => 'required',
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


        try {
            DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'prenom' => $request->prenom,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'force_password_change' => true,
                    'agence_id' => $request->agence_id,
                    'departement_id' => $request->departement_id,
                    'service_id' => $request->service_id,
                    'superieur' => false,
                ]);

                // Rôle par défaut, tracé dans l'historique des rôles.
                app(JournalRoles::class)->attribuer($user, 'Demandeur', auth()->user(), 'creation');

                if ($request->has('superieur')) {
                    app(DesignationSuperieur::class)->designer($user, auth()->user(), $this->superieurRemplace($request));
                }
            });
        } catch (RefusOrganisation $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec rôle Demandeur');
    }

    public function show($id)
    {
        $user = User::with(['roles', 'agence', 'departement', 'service'])->findOrFail($id);

        $this->authorize('view', $user);

        return view('users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('update', $user);

        $roles = Role::all();
        $agences = Agence::all();
        $departements = Departement::all();
        $services = Service::all();
        $entite = $user->entiteDeValidation();
        $superieursRemplacables = $entite
            ? DesignationSuperieur::titulaires($entite, $user->id)
            : collect();

        return view('users.edit', compact('user', 'roles', 'agences', 'departements', 'services', 'superieursRemplacables'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $this->authorize('update', $user);

        $profilPersonnel = auth()->id() == $user->id;

        /*
    |--------------------------------------------------------------------------
    | Validation des données générales
    |--------------------------------------------------------------------------
    */
        if (!$profilPersonnel) {

            $request->validate([
                'name' => 'required',
                'prenom' => 'required',
                'email' => 'required|email',
                'roles' => 'nullable|array',
                'roles.*' => 'string|exists:roles,name',
            ]);

            // Socle des droits : aucun rôle hors de ce que l'acteur peut
            // attribuer (SuperAdmin réservé au SuperAdmin, jamais ses propres
            // rôles, jamais le retrait du dernier SuperAdmin).
            $rolesDemandes = array_values(array_unique($request->input('roles', [])));
            Gate::authorize('attribuerRoles', [$user, $rolesDemandes]);

            // Vérification du supérieur hiérarchique : remplacée par la
            // désignation d'un remplaçant (décision validée, voir plus bas).

            /*
        |--------------------------------------------------------------------------
        | Informations, rôles, puis fonction de supérieur, en une transaction
        |--------------------------------------------------------------------------
        | Le rôle Superieur suit la case « supérieur » (DesignationSuperieur) :
        | il n'est ni attribué ni retiré par les cases de rôles. Chaque
        | changement de rôle est enregistré (JournalRoles).
        */
            try {
                DB::transaction(function () use ($user, $request, $rolesDemandes) {
                    $user->update([
                        'name' => $request->name,
                        'prenom' => $request->prenom,
                        'email' => $request->email,
                        'agence_id' => $request->agence_id,
                        'departement_id' => $request->departement_id,
                        'service_id' => $request->service_id,
                    ]);

                    app(JournalRoles::class)->synchroniser($user, $rolesDemandes, auth()->user(), 'formulaire', ['Superieur']);

                    if ($request->has('superieur')) {
                        app(DesignationSuperieur::class)->designer($user->fresh(), auth()->user(), $this->superieurRemplace($request));
                    } elseif ($user->superieur || $user->hasRole('Superieur')) {
                        app(DesignationSuperieur::class)->retirer($user->fresh(), auth()->user());
                    }
                });
            } catch (RefusOrganisation $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Gestion du mot de passe
    |--------------------------------------------------------------------------
    */

        if ($profilPersonnel) {

            // Son propre mot de passe est obligatoire
            $request->validate(
                [
                    'password' => [
                        'required',
                        'confirmed',
                        'min:12',
                        'regex:/[A-Z]/',
                        'regex:/[a-z]/',
                        'regex:/[0-9]/',
                        'regex:/[@$!%*#?&]/'
                    ]
                ],
                [
                    'password.required' =>
                    'Veuillez saisir un nouveau mot de passe.',

                    'password.confirmed' =>
                    'Les mots de passe ne correspondent pas.',

                    'password.min' =>
                    'Le mot de passe doit contenir au moins 12 caractères.',

                    'password.regex' =>
                    'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.'
                ]
            );


            // Vérifier que le nouveau mot de passe est différent
            if (Hash::check($request->password, $user->password)) {

                return back()
                    ->withErrors([
                        'password' =>
                        'Le nouveau mot de passe doit être différent du mot de passe actuel.'
                    ])
                    ->withInput();
            }


            // Modifier le mot de passe
            $user->update([
                'password' => Hash::make($request->password),
                'force_password_change' => false,
            ]);


            return redirect()
                ->route('mon.profil')
                ->with(
                    'success',
                    'Mot de passe modifié avec succès'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Admin : mot de passe facultatif
    |--------------------------------------------------------------------------
    */

        if ($request->filled('password')) {

            $request->validate(
                [
                    'password' => [
                        'confirmed',
                        'min:8'
                    ]
                ],
                [
                    'password.confirmed' =>
                    'Les mots de passe ne correspondent pas.',

                    'password.min' =>
                    'Le mot de passe doit contenir au moins 8 caractères.'
                ]
            );


            $user->update([
                'password' => Hash::make($request->password),
                'force_password_change' => true,
            ]);
        }


        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Utilisateur modifié'
            );
    }
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Q9 (décision validée) : un compte qui porte un historique métier
        // n'est jamais supprimé physiquement ; il est désactivé et conservé.
        if (UserPolicy::porteUnHistorique($user)) {
            $this->authorize('desactiver', $user);
            app(CycleDeVieComptes::class)->desactiver($user, auth()->user());

            return redirect()->route('users.index')->with(
                'success',
                'Ce compte possède un historique : il a été désactivé et conservé, pas supprimé.'
            );
        }

        // Sans historique : suppression physique (jamais soi-même, SuperAdmin
        // réservé au SuperAdmin, jamais le dernier SuperAdmin).
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index');
    }

    //Export Excel
    public function exportExcel()
    {
        $this->authorize('viewAny', User::class);

        return Excel::download(
            new UsersExport,
            'users.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        $this->authorize('viewAny', User::class);

        return Excel::download(
            new UsersExport,
            'users.csv'
        );
    }

    //Blocage et deblocage d'un utilisateur
    public function bloquer($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('bloquer', $user);

        app(CycleDeVieComptes::class)->bloquer($user);

        return back()->with(

            'success',

            'Utilisateur bloqué'

        );
    }

    public function debloquer($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('bloquer', $user);

        app(CycleDeVieComptes::class)->debloquer($user);

        return back()->with(

            'success',

            'Utilisateur débloqué'

        );
    }

    // Q9 : réactivation d'un compte désactivé (utilisateurs habilités seulement)
    public function reactiver($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('desactiver', $user);

        app(CycleDeVieComptes::class)->reactiver($user);

        return back()->with('success', 'Compte réactivé');
    }

    /** Supérieurs en fonction (pour le choix du supérieur remplacé). */
    private function superieursEnFonction()
    {
        return User::with(['agence', 'departement'])
            ->where('superieur', 1)
            ->whereNull('desactive_le')
            ->orderBy('name')
            ->get();
    }

    private function superieurRemplace(Request $request): ?User
    {
        $id = $request->input('superieur_remplace_id');

        return $id ? User::find($id) : null;
    }

    public function getDepartementsParAgence(
        $agenceId
    ) {

        return Departement::where(
            'agence_id',
            $agenceId
        )
            ->orderBy('nom')
            ->get();
    }

    public function getServicesParDepartement(
        $departementId
    ) {

        return Service::where(
            'departement_id',
            $departementId
        )
            ->orderBy('nom')
            ->get();
    }


    public function importForm()
    {
        $this->authorize('create', User::class);

        return view(
            'users.import'
        );
    }

    public function import(
        Request $request
    ) {

        // dd('Import appelé');
        $this->authorize('create', User::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240'
        ]);

        // Lot 1, L1-5 : le mot de passe initial des comptes importés est lu
        // dans la configuration du serveur (.env), jamais dans le code.
        // Sans valeur configurée, l'import est annulé avant toute création.
        if (blank(config('gestok.mot_de_passe_initial'))) {
            return back()->with(
                'error',
                "Import annulé : le mot de passe initial n'est pas configuré sur le serveur (GESTOK_MOT_DE_PASSE_INITIAL)."
            );
        }

        // Socle des droits : import tout ou rien ; les rôles du fichier sont
        // contrôlés ligne par ligne (UsersImport).
        try {
            DB::transaction(function () use ($request) {
                Excel::import(
                    new UsersImport(auth()->user()),
                    $request->file('file')
                );
            });
        } catch (ImportInvalide $e) {
            // Aucune création conservée ; erreurs affichées ligne par ligne
            // par la vue d'import (bloc d'erreurs existant).
            return back()->withErrors(['import' => $e->erreurs]);
        }



        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Import effectué avec succès.'
            );
    }
}
