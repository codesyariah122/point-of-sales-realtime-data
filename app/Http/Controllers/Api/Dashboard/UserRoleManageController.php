<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Roles;
use App\Models\UserRole;

class UserRoleManageController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Gate::allows('user-role')) return $next($request);

            abort(403, 'Anda tidak memiliki cukup hak akses');
        });
    }

    public function user_role(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'role_id' => 'required|integer'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $users = User::whereId($request->user_id)->with('roles')->get();
            $roles = Roles::whereId($request->role_id)->get();

            $check_roles = UserRole::whereUserId($request->user_id)->get();

            if (count($check_roles) > 0) {
                $user_role = UserRole::findOrFail($check_roles[0]->id);
                $user_role->roles_id = $request->role_id;
                $user_role->save();
                $update_user_role = User::whereId($request->user_id)->with('roles')->get();
            } else {
                $user_role = new UserRole;
                $user_role->user_id = $users[0]->id;
                $user_role->roles_id = $roles[0]->id;
                $user_role->save();

                $update_user_role = User::whereId($request->user_id)->with('roles')->get();
            }

            return response()->json([
                'message' => 'User role management!',
                'data' => $update_user_role
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
