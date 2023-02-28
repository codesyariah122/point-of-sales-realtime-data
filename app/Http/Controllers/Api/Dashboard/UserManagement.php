<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Profile;
use App\Models\UserRole;
use App\Events\EventNotification;
use App\Models\Roles;
use Auth;
use App\Helpers\UserHelpers;

class UserManagement extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $helpers;

    public function __construct()
    {
        //     $this->middleware('auth');
        //     $this->middleware(function ($request, $next) {
        //         if (Gate::allows('user-management')) return $next($request);
        //         abort(403, 'Anda tidak memiliki cukup hak akses');
        //     });
        $this->helpers = new UserHelpers;   
    }

    public function index()
    {
        try {
            $users = User::whereNull('deleted_at')
            ->with('profiles')
            ->with('roles')
            ->orderBy('id', 'DESC')
            ->paginate(5);
            
            return response()->json([
                'message' => 'User data lists',
                'data' => $users
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:25',
                'email' => 'required|email|unique:users,email',
                'password'  => [
                    'required', Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                ],
                'roles' => 'required',
                'status' => 'required',
                // 'username' => 'required|string|regex:/\w*$/|unique:profiles,username|max:10',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_roles = $this->helpers;
            $roles = json_decode($request->user()->roles[0]->roles);
            if($check_roles->checkRoles($request->user())):
                return response()->json([
                    'success' => false,
                    'message' => "Roles {$roles[0]}, tidak di ijinkan mengupdate data"
                ]);
            endif;

            $role_id = $request->roles;
            $check_user_role = Roles::whereId($role_id)->get();

            if (count($check_user_role) > 0) {
                $new_user = new User;
                $new_user->name = $request->name;
                $new_user->email = $request->email;
                $new_user->password = Hash::make($request->password);
                $new_user->status = $request->status;
                $new_user->save();
                $new_profile = new Profile;
                $new_profile->username = $request->username ? $request->username : trim(preg_replace('/\s+/', '_', $request->name));
                if ($request->file('photo')) {
                    $file = $request->file('photo')->store(trim(preg_replace('/\s+/', '', $new_user->name)) . '/image/profile', 'public');
                    $new_profile->photo = $file;
                }
                $new_profile->save();
                $user_profile_id = $new_profile->id;
                $new_user->profiles()->sync($user_profile_id);

                $role_user = new UserRole;
                $role_user->user_id = $new_user->id;
                $role_user->roles_id = $role_id;
                $role_user->save();

                $add_new_user = User::whereId($new_user->id)
                ->with('profiles')
                ->with('roles')
                ->get();

                $data_event = [
                    'notif' => "{$add_new_user[0]->name}, berhasil di tambahkan!",
                    'data' => $add_new_user
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => 'User baru berhasil dibuat',
                    'data' => $add_new_user
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User roles is not defined!'
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user_detail = User::whereId($id)
            ->with('profiles')
            ->with('roles')
            ->get();

            if (count($user_detail) % 2 == 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'User detail data',
                    'data' => $user_detail
                ]);
            }

            return response()->json([
                'message' => 'User not found'
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $roles = json_decode($request->user()->roles[0]->roles);
            if($roles[0] !== "OWNER" || $roles[0] !== "ADMIN") {
                return response()->json([
                    'success' => false,
                    'message' => "Roles $roles[0], tidak di ijinkan mengupdate data"
                ]);
            }

            $user = User::with('profiles')->find($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|max:25',
                'email' => 'required|email|unique:users,email',
                'password'  => [
                    'required', Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                ],
                'roles' => 'required',
                'status' => 'required',
                'username' => 'required|string|regex:/\w*$/|unique:profiles,username|max:10',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_roles = $this->helpers;
            $roles = json_decode($request->user()->roles[0]->roles);
            if($check_roles->checkRoles($request->user())):
                return response()->json([
                    'success' => false,
                    'message' => "Roles {$roles[0]}, tidak di ijinkan mengupdate data"
                ]);
            endif;

            $update_user = new User;
            $update_user->name = $request->name;
            $update_user->email = $request->email;
            $update_user->phone = $request->phone;
            $update_user->status = $request->status;
            $update_user->save();
            $update_profile = new Profile;
            $update_profile->username = trim(preg_replace('/\s+/', '_', $request->username));
            if ($request->file('photo')) {
                $file = $request->file('photo')->store(trim(preg_replace('/\s+/', '', $update_user->name)) . '/image/profile', 'public');
                $update_profile->photo = $file;
            }
            $update_profile->about = $request->about;
            $update_profile->address = $request->address;
            $update_profile->post_code = $request->post_code;
            $update_profile->city = $request->city;
            $update_profile->district = $request->district;
            $update_profile->province = $request->province;
            $update_profile->country = $request->country;
            $update_profile->save();

            $new_user_updated = User::whereId($update_user->id)->with('profiles')->get();

            return response()->json([
                'message' => "Update user {$user->name}, berhasil",
                'data' => $new_user_updated
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $roles = json_decode($request->user()->roles[0]->roles);
            if($roles[0] === "CASHIER") {
                return response()->json([
                    'success' => false,
                    'message' => "Roles $roles[0], tidak di ijinkan menghapus data"
                ]);
            }

            $delete_user = User::findOrFail($id);
            $delete_user->profiles()->delete();
            $delete_user->delete();

            $data_event = [
                'notif' => "{$delete_user['name']}, berhasil di hapus!",
                'data' => $delete_user
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "User {$delete_user['name']} berhasil di hapus",
                'data' => $delete_user
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
