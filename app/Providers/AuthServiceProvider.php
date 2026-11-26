<?php

namespace App\Providers;

use App\Autorisation\Capacites;
use App\Models\Demande;
use App\Models\User;
use App\Policies\DemandePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Demande::class => DemandePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Socle des droits : une Gate par capacité (définition unique dans
        // App\Autorisation\Capacites), utilisée par le middleware « can: ».
        foreach (Capacites::noms() as $capacite) {
            Gate::define($capacite, fn (User $user) => Capacites::autorise($user, $capacite));
        }
    }
}
