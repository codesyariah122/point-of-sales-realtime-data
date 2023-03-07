<?php
/**
 * @author: pujiermanto@gmail.com
 * @param SessionExpires at middleware
 * @param Flush Session Auto Logout
 * */

namespace App\Http\Controllers\Api\Fitur;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\{LoginController, RegisterController};
use App\Http\Controllers\Api\Dashboard\{RolesManagement, UserManagement, UserRoleManageController, MenuManagement, SubMenuManagement, UserAccessMenuController, ProductManagement, CategoryManagement, CustomerManagement, OrderManagement, SupplierManagement};

Route::middleware(['auth:api', 'cors', 'json.response', 'session.expired'])->prefix('v1/fitur')->group(function () {
    Route::get('/user-login', [LoginController::class, 'userIsLogin']);
    
    // User management
    Route::resource('/user-management', UserManagement::class);

    // Trashed data
    Route::get('/trashed', [WebFiturController::class, 
        'trash']);
    Route::put('/trashed/{id}', [WebFiturController::class, 'restoreTrash']);
    Route::delete('/trashed/{id}', [WebFiturController::class, 'deletePermanently']);

    // Role management
    Route::resource('/role-management', RolesManagement::class);

    // Menu mangement
    Route::resource('/menu-management', MenuManagement::class);

    Route::resource('/submenu-management', SubMenuManagement::class);
    Route::post('/access-menu', [UserAccessMenuController::class, 'user_access_menu']);
    Route::get('/access-menu', [UserAccessMenuController::class, 'access_menu_list']);
    Route::post('/user-role', [UserRoleManageController::class, 'user_role']);

    // Product
    Route::resource('/category-management', CategoryManagement::class);
    Route::resource('/product-management', ProductManagement::class);

    // Customer
    Route::resource('/customer-management', CustomerManagement::class);

    // Supplier
    Route::resource('/supplier-management', SupplierManagement::class);

    // Order
    Route::resource('/order-management', OrderManagement::class);

    // Barcode fitur
    Route::post('/barcode', [WebFiturController::class, 'barcode_fitur']);
    Route::post('/qrcode', [WebFiturController::class, 'qrcode_fitur']);

    // Trash data
    Route::get('/total-trash', [WebFiturController::class, 'totalTrash']);

    // Total Data
    Route::get('/total-data', [WebFiturController::class, 'totalData']);
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
