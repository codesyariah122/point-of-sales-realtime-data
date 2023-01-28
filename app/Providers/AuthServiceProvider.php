<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use App\Models\User;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();

        Passport::personalAccessTokensExpireIn(Carbon::now()->addDays(1));

        Gate::define('user-management', function ($user) {
            // return count(array_intersect(["ADMIN", "OWNER"], json_decode($user->roles))) ? true :  false;
            $user_id = $user->id;
            $roles = User::whereId($user_id)->with('roles')->get();
            $role = json_decode($roles[0]->roles[0]->roles);

            return count(array_intersect(["ADMIN", "OWNER"], $role)) ? true :  false;
        });

        Gate::define('role-management', function ($user) {
            // return count(array_intersect(["ADMIN", "OWNER"], json_decode($user->roles))) ? true :  false;
            $user_id = $user->id;
            $roles = User::whereId($user_id)->with('roles')->get();
            $role = json_decode($roles[0]->roles[0]->roles);

            return count(array_intersect(["ADMIN", "OWNER"], $role)) ? true :  false;
        });
    }
}
