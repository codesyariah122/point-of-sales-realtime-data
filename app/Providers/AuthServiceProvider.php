<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\FitureHelpers;
use Auth;
use App\Models\User;
use App\Models\Profile;
use App\Models\Login;

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

    public function boot(Request $request)
    {
        $this->registerPolicies();


        Passport::routes();
        // Passport::personalAccessTokensExpireIn(Carbon::now()->addSecond(30));
        // if (Passport::personalAccessTokensExpireIn(Carbon::now()->addSecond(30))) {

        //     if ($request->header('Authorization') !== NULL) {
        //         $token = $request->header('Authorization');
        //         $token_login = explode(" ", $token)[1];
        //         $user_token = Login::where('user_token_login', $token_login)->get();

        //         $user_login = User::findOrFail($user_token[0]->user_id);
        //         $user_login->is_login = 0;
        //         $user_login->expires_at = NULL;
        //         $user_login->save();
        //         $user_token->delete();
        //     }
        // }


        self::set_data();

        $gates = new FitureHelpers($this->helpers);

        $gates->GatesAccess();
    }
}
