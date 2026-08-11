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

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'agence', 'departement', 'service'])
            ->latest()
            ->get();

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

        if ($request->has('superieur')) {
            // Directeur agence
            if ($request->agence_id) {
                $agenceExiste = User::where(
                    'agence_id',
                    $request->agence_id
                )
                    ->where(
                        'superieur',
                        1
                    )
                    ->exists();

                if ($agenceExiste) {
                    return back()
                        ->withErrors([

                            'superieur' =>

                            'Cette agence possède déjà un directeur.'

                        ])
                        ->withInput();
                }
            }

            // Directeur département
            if ($request->departement_id) {
                $departementExiste = User::where(
                    'departement_id',
                    $request->departement_id
                )
                    ->where(
                        'superieur',
                        1
                    )
                    ->exists();

                if ($departementExiste) {
                    return back()
                        ->withErrors([

                            'superieur' =>

                            'Ce département possède déjà un directeur.'

                        ])
                        ->withInput();
                }
            }
        }

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


        $user = User::create([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'force_password_change' => true,
            'agence_id' => $request->agence_id,
            'departement_id' => $request->departement_id,
            'service_id' => $request->service_id,
            'superieur' => $request->has('superieur'),
        ]);

        if (
            $user->superieur
        ) {
            $user->assignRole(
                'Superieur'
            );
        }
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

        $profilPersonnel = auth()->id() == $user->id;

        if (!$profilPersonnel) {

            $user->update([
                'name' => $request->name,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'agence_id' => $request->agence_id,
                'departement_id' => $request->departement_id,
                'service_id' => $request->service_id,
                'superieur' => $request->has('superieur')
            ]);
        }

        if (!$profilPersonnel) {

            if ($request->has('superieur')) {

                if (!$user->hasRole('Superieur')) {

                    $user->assignRole('Superieur');
                }
            } else {

                $user->removeRole('Superieur');
            }
        }
        if (is_null($request->password) || trim($request->password) === '') {
            return back()
                ->withErrors([
                    'password' => 'Veuillez saisir un nouveau mot de passe.'
                ])
                ->withInput();
        }

        // dd(
        //     $profilPersonnel,
        //     $request->password,
        //     strlen($request->password)
        // );
        if ($profilPersonnel) {

            $request->validate(
                [
                    'password' => [
                        'confirmed',
                        'min:12',
                        'regex:/[A-Z]/',
                        'regex:/[a-z]/',
                        'regex:/[0-9]/',
                        'regex:/[@$!%*#?&]/'
                    ]
                ],
                [
                    'password.confirmed' =>
                    'Les mots de passe ne correspondent pas.',

                    'password.min' =>
                    'Le mot de passe doit contenir au moins 12 caractères.',

                    'password.regex' =>
                    'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.'
                ]
            );

            if (
                Hash::check(
                    $request->password,
                    $user->password
                )
            ) {

                return back()->withErrors([
                    'password' =>
                    'Le nouveau mot de passe doit être différent du mot de passe actuel.'
                ]);
            }
        } else {

            $request->validate(
                [
                    'password' => [
                        'confirmed',
                        'min:8'
                    ]
                ]
            );
        }

        $user->update([
            'password' => Hash::make($request->password),
            'force_password_change' =>
            $profilPersonnel
                ? false
                : true

        ]);


        if (!$profilPersonnel) {

            $user->syncRoles(
                $request->roles ?? []
            );
        }

        if ($profilPersonnel) {

            return redirect()
                ->route('mon.profil')
                ->with(
                    'success',
                    'Mot de passe modifié avec succès'
                );
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
        User::findOrFail($id)->delete();

        return redirect()->route('users.index');
    }

    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new UsersExport,
            'users.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new UsersExport,
            'users.csv'
        );
    }

    //Blocage et deblocage d'un utilisateur
    public function bloquer($id)
    {
        $user = User::findOrFail($id);

        $user->update([

            'bloque' => true

        ]);

        return back()->with(

            'success',

            'Utilisateur bloqué'

        );
    }

    public function debloquer($id)
    {
        $user = User::findOrFail($id);

        $user->update([

            'bloque' => false

        ]);

        return back()->with(

            'success',

            'Utilisateur débloqué'

        );
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
        return view(
            'users.import'
        );
    }

    public function import(
        Request $request
    ) {

        // dd('Import appelé');
        $request->validate([
            'file' => 'required|file'
        ]);



        Excel::import(

            new UsersImport,

            $request->file('file')

        );



        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Import effectué avec succès.'
            );
    }
}
