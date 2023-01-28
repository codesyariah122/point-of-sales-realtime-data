<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Profile;
use App\Models\Login;
use App\Helpers\UserHelpers;


class LoginController extends Controller
{
    /**
     * login
     *
     * @param  mixed $request
     * @return void
     */
    private $helper;

    public function __construct()
    {
        $this->helper = new UserHelpers;
    }

    private function forbidenIsUserLogin($isLogin)
    {
        return $isLogin ? true : false;
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'not_found' => true,
                    'message' => 'Your email not registered !'
                ]);
            } else {

                if (!Hash::check($request->password, $user->password)) :
                    return response()->json([
                        'success' => false,
                        'message' => 'Your password its wrong'
                    ]);
                else :
                    if ($user->status === "INACTIVE") {
                        return response()->json([
                            'in_active' => true,
                            'message' => "{$user->name}, Akun Tidak Aktiv.",
                            'data' => $user
                        ]);
                    } else {
                        if ($this->forbidenIsUserLogin($user->is_login)) {
                            $last_login = Carbon::parse($user->last_login)->diffForHumans();
                            return response()->json([
                                'is_login' => true,
                                'message' => "Akun sedang digunakan {$last_login}",
                                'quote' => 'Please check the notification again!'
                            ]);
                        }

                        $token = $user->createToken('authToken')->accessToken;

                        $user_login = User::findOrFail($user->id);
                        $user_login->is_login = 1;

                        if ($request->remember_me !== NULL) {
                            $user_login->expires_at = Carbon::now()->addRealDays(30);
                            $user_login->remember_token = Str::random(32);
                        }
                        $user_login->expires_at = Carbon::now()->addRealMinutes(60);
                        $user_login->last_login = Carbon::now();
                        $user_login->save();
                        $user_id = $user_login->id;
                        $logins = new Login;
                        $logins->user_id = $user_id;
                        $logins->user_token_login = $token;
                        $logins->save();
                        $login_id = $logins->id;

                        // sync pivot table
                        $user->logins()->sync($login_id);

                        $userIsLogin = User::whereId($user_login->id)
                            ->with('roles')
                            ->with('profiles')
                            ->with('logins')
                            ->get();

                        return response()->json([
                            'success' => true,
                            'message' => 'Login Success!',
                            'data'    => $userIsLogin,
                            'remember_token' => $user_login->remember_token
                        ]);
                    }
                endif;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * logout
     *
     * @param  mixed $request
     * @return void
     */
    public function logout(Request $request)
    {
        try {
            $user = User::findOrFail($request->user()->id);
            $user->is_login = 0;
            $user->expires_at = null;
            $user->remember_token = null;
            $user->save();


            $removeToken = $request->user()->tokens()->delete();
            $delete_login = Login::whereUserId($user->id);
            $delete_login->delete();

            if ($removeToken) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logout Success!',
                    'data' => $user
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function userIsLogin()
    {
        try {
            $user = User::whereIsLogin(1)
                ->with('profiles')
                ->with('roles')
                ->with('logins')
                ->first();
            if ($user !== NULL) {
                return response()->json([
                    'message' => 'User data is login',
                    'data' => $user
                ], 200);
            }
            return response()->json([
                'not_login' => true,
                'message' => 'Anauthenticated'
            ]);
        } catch (\Throwable $th) {
            return response()->json(['valid' => auth()->check()]);
        }
    }
}
