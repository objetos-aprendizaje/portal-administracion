<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        DB::table('users')->where('email', 'admin@admin.com')->delete();
    }
};
