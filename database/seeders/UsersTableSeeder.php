<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = DB::table('users')->where('email', 'admin@admin.com')->first();

        if($user) return;

        $user_uid = generate_uuid();

        DB::table('users')->insert([
            'uid' => $user_uid,
            'email' => 'admin@admin.com',
            'password' => password_hash('admin-poa-2024', PASSWORD_BCRYPT),
            'first_name' => 'admin',
            'last_name' => 'poa',
            'nif' => '12345678A',
        ]);

        $admin_rol = DB::table('user_roles')->where('code', 'ADMINISTRATOR')->first();

        DB::table('user_role_relationships')->insert([
            'uid' => generate_uuid(),
            'user_uid' => $user_uid,
            'user_role_uid' => $admin_rol->uid,
        ]);
    }
}
