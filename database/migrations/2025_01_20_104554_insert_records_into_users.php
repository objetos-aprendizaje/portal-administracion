<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userUid = generateUuid();

        DB::table('users')->insert([
            'uid' => $userUid,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@admin.com',
            'password' => password_hash('12345678', PASSWORD_BCRYPT),
            'verified' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rol de administrador
        $adminRole = DB::table('user_roles')->where('code', 'ADMINISTRATOR')->first();

        DB::table('user_role_relationships')->insert([
            'uid' => generateUuid(),
            'user_uid' => $userUid,
            'user_role_uid' => $adminRole->uid,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
