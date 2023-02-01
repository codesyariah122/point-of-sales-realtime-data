<?php


namespace App\Http\Controllers\Api\Fitur;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\{LoginController, RegisterController};
use App\Http\Controllers\Api\Dashboard\{RolesManagement, UserManagement, UserRoleManageController, MenuManagement, SubMenuManagement, UserAccessMenuController};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->prefix('v1/fitur')->group(function () {
    Route::get('/user-login', [LoginController::class, 'userIsLogin']);
    Route::resource('/user-management', UserManagement::class);
    Route::resource('/role-management', RolesManagement::class);
    Route::resource('/menu-management', MenuManagement::class);
    Route::resource('/submenu-management', SubMenuManagement::class);
    Route::post('/access-menu', [UserAccessMenuController::class, 'user_access_menu']);
    Route::get('/access-menu', [UserAccessMenuController::class, 'access_menu_list']);
    Route::post('/user-role', [UserRoleManageController::class, 'user_role']);
});

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:api');
});

Route::prefix('v1')->group(function () {
    Route::get('/test', function () {
        return response()->json([
            'message' => 'test api'
        ]);
    });
});


Route::prefix('v1/web')->group(function () {
    Route::get('/context', [WebFiturController::class, 'web_data']);
});
