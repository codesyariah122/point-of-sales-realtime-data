<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu;
use App\Models\SubMenu;

class CreateUserRoleMenuSubmenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('user_role_menu_submenu', function (Blueprint $table) {
            $menus = [];
            $submenus = [];
            $list_menus = Menu::all();
            foreach ($list_menus as $menu) {
                array_push($menus, $menu->menu);
            }
            $list_submenus = SubMenu::all();
            foreach ($list_submenus as $sub_menu) {
                array_push($submenus, $sub_menu);
            }

            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('roles_id')->nullable();
            $table->enum('menu', $menus)->nullable();
            $table->enum('sub_menu', $submenus)->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('roles_id')->references('id')->on('roles_user')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_role_menu_submenu');
    }
}
