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
use App\Events\EventNotification;


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

            $user = User::where('email', $request->email)->get();


            if (count($user) === 0) {
                return response()->json([
                    'not_found' => true,
                    'message' => 'Your email not registered !'
                ]);
            } else {

                if (!Hash::check($request->password, $user[0]->password)) :
                    return response()->json([
                        'success' => false,
                        'message' => 'Your password its wrong'
                    ]);
                else :
                    if ($user[0]->status === "INACTIVE") {
                        return response()->json([
                            'in_active' => true,
                            'message' => "{$user[0]->name}, Akun Tidak Aktiv.",
                            'data' => $user[0]
                        ]);
                    } else {
                        if ($this->forbidenIsUserLogin($user[0]->is_login)) {
                            $last_login = Carbon::parse($user[0]->last_login)->diffForHumans();
                            return response()->json([
                                'is_login' => true,
                                'message' => "Akun sedang digunakan {$last_login}",
                                'quote' => 'Please check the notification again!'
                            ]);
                        }

                        $token = $user[0]->createToken($user[0]->name)->accessToken;

                        $user_login = User::findOrFail($user[0]->id);
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
                        $user[0]->logins()->sync($login_id);

                        $userIsLogin = User::whereId($user_login->id)
                            ->with('roles')
                            ->with('profiles')
                            ->with('logins')
                            ->get();


                        $data_event = [
                            'notif' => "{$user[0]->name}, baru saja login!",
                            'data' => $userIsLogin
                        ];

                        event(new EventNotification($data_event));

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

            $data_event = [
                'notif' => "{$user->name}, telah keluar!",
                'data' => $user
            ];

            event(new EventNotification($data_event));

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

    public function userIsLogin(Request $request)
    {
        try {
            $user = $request->user();

            $user_login = User::whereEmail($user->email)
                ->with('profiles')
                ->with('roles')
                ->with('logins')
                ->get();
            if (count($user_login) > 0) {
                return response()->json([
                    'message' => 'User data is login',
                    'data' => $user_login
                ], 200);
            } else {
                return response()->json([
                    'not_login' => true,
                    'message' => 'Anauthenticated'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json(['valid' => auth()->check()]);
        }
    }
}
