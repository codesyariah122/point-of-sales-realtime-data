<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\{User, Profile, Roles};

class AdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $administrator = new User;
        $administrator->name = "COD(o.t)";
        $administrator->email = "codot.2023@gmail.com";
        $administrator->phone = "6288222668778";
        $administrator->password = Hash::make("Bismillah_123654");
        // $administrator->roles = json_encode(["OWNER"]);
        $administrator->status = "ACTIVE";
        $administrator->is_login = 0;
        $administrator->save();
        $administrator_profile = new Profile;
        $administrator_profile->username = trim(preg_replace('/\s+/', '_', strtolower($administrator->name)));
        $administrator_profile->photo = NULL;
        $administrator_profile->about = "Distributor buah import dengan kualitas terbaik di Surabaya.";
        $administrator_profile->address = 'Jl. Tengger Raya 3A/8 RT. 02  Kel. Kandangan. Kec. Benowo';
        $administrator_profile->city = 'Surabaya';
        $administrator_profile->district = 'Benowo';
        $administrator_profile->province = 'East Java';
        $administrator_profile->post_code = '60191';
        $administrator_profile->save();
        $administrator->profiles()->sync($administrator_profile->id);
        $roles = new Roles;
        $roles->roles = json_encode(["OWNER"]);
        $roles->permission = json_encode(["show", "add", "edit", "delete"]);
        $roles->save();
        $administrator->roles()->sync($roles->id);
        $this->command->info("User admin created successfully");
    }
}
