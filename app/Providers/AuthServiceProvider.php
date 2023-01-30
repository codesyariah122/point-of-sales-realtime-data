<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Carbon\Carbon;
use App\Helpers\FitureHelpers;

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
    protected $helpers = [];
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */

    public function __contstruct($data)
    {
        $this->helpers = $data;
    }

    public function set_data()
    {
        $gate_data = [
            'user-management',
            'role-management',
            'menu-management',
            'submenu-management'
        ];
        self::__contstruct($gate_data);
    }

    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();

        Passport::personalAccessTokensExpireIn(Carbon::now()->addDays(1));

        self::set_data();

        $gates = new FitureHelpers($this->helpers);

        $gates->GatesAccess();
    }
}
