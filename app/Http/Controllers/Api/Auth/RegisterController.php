<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\UserHelpers;
use App\Models\User;
use App\Models\Profile;

class RegisterController extends Controller
{
    /**
     * register
     *
     * @param  mixed $request
     * @return void
     */
    public function register(Request $request)
    {
        try {
            $helper = new UserHelpers;
            $validator = Validator::make($request->all(), [
                'name'      => 'required',
                'email'     => 'required|email|unique:users',
                'password'  => [
                    'required', 'confirmed', Password::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols()
                ]
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = new User;
            $user->name = strip_tags($request->name);
            $user->email = strip_tags($request->email);
            $user->phone = $request->phone ? $helper->formatPhoneNumber($request->phone) : null;
            $user->password = Hash::make($request->password);
            // $user->roles = json_encode($request->roles ? $request->roles : ['CUSTOMER']);
            $user->status = 'INACTIVE';
            $user->is_login = 0;
            $user->save();

            // saving profile user table
            $user_profile = new Profile;
            $user_profile->username = trim(preg_replace('/\s+/', '_', $user->name));
            if ($request->file('photo')) {
                $file = $request->file('photo')->store(trim(preg_replace('/\s+/', '', $user->name)) . '/image/profile', 'public');
                $user_profile->photo = $file;
            }
            $user_profile->about = $request->about ? $request->about : null;
            $user_profile->save();
            $profile_id = $user_profile->id;
            $user->profiles()->sync($profile_id);

            return response()->json([
                'success' => true,
                'message' => 'Register Success!',
                'data'    => $user
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
