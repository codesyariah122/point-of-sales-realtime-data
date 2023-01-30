<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\UserAccessMenu;
use App\Models\User;
use App\Models\Roles;
use App\Models\Menu;
use App\Models\SubMenu;
use App\Events\EventNotification;

class UserAccessMenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Gate::allows('access-menu')) return $next($request);
            abort(403, 'Anda tidak memiliki cukup hak akses');
        });
    }

    public function user_access_menu(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user' => 'required',
                'roles' => 'required',
                'menu' => 'required',
                'sub_menu' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $access_menu = new UserAccessMenu;
            $access_menu->user_id = $request->user;
            $access_menu->roles_id = $request->roles;
            $access_menu->menu_id = $request->menu;
            $access_menu->sub_menu_id = $request->sub_menu;
            $access_menu->save();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
