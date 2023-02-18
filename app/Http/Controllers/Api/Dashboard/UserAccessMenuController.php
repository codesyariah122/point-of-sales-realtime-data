<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use App\Models\UserAccessMenu;
use App\Models\User;
use App\Models\Menu;
use App\Models\SubMenu;

class UserAccessMenuController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    //     $this->middleware(function ($request, $next) {
    //         if (Gate::allows('access-menu')) return $next($request);
    //         abort(403, 'Anda tidak memiliki cukup hak akses');
    //     });
    // }

    public function access_menu_list(Request $request)
    {
        try {
            $user = $request->user();
            $user_logins = User::whereId($user->id)
                ->with('roles')
                ->get();
            $user_roles = $user_logins[0]->roles[0]->id;

            $menus = Menu::whereJsonContains('roles', $user_roles)
                // ->whereHas('sub_menus', function($role) use ($roles_json) {
                //     $role->whereJsonContains('roles', $roles_json);
                // })
                // ->whereRelation('sub_menus', function($role) use ($user_roles) {
                //     $role->whereJsonContains('roles', $user_roles);
                // })
                ->with('sub_menus')
                ->get();

            $sub_menus = SubMenu::whereJsonContains('roles', $user_roles)
                ->with('menus')
                ->get();

            return response()->json([
                'message' => 'List menu of users',
                'users' => $user_logins,
                'menus' => $menus,
                // 'sub_menus' => $sub_menus
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function user_access_menu(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'roles' => 'required',
                'menus' => 'required',
                'sub_menus' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $access_menu = new UserAccessMenu;
            $access_menu->roles_id = $request->roles;
            $access_menu->menus = $request->menus;
            $access_menu->sub_menus = $request->sub_menus;
            $access_menu->save();

            return response()->json([
                'message' => 'Added new role access menu',
                'data' => $access_menu
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function menu_by_roles(Request $request)
    {
        try {
            $menus = UserAccessMenu::whereRolesId($request->roles)->get();
            var_dump($menus);
            die;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
