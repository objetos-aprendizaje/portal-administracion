<?php

namespace Database\Seeders;

use App\Models\UserRoleRelationshipsModel;
use App\Models\UserRolesModel;
use App\Models\UsersModel;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userRoles = UserRolesModel::all();

        $userUid = generate_uuid();
        UsersModel::factory()
            ->create([
                'uid' => $userUid,
                'email' => 'test@test.com'
            ]);

        foreach ($userRoles as $rol) {
            $this->assignRole($userUid, $rol->uid);
        }

        // Usuario con un solo rol
        foreach ($userRoles as $rol) {
            for ($i = 0; $i <= 2; $i++) {
                $userUid = $this->createUser();

                $this->assignRole($userUid, $rol->uid);
            }
        }
    }

    private function createUser()
    {
        $userUid = generate_uuid();
        UsersModel::factory()
            ->create([
                'uid' => $userUid,
            ]);
        return $userUid;
    }

    private function assignRole($userUid, $roleUid)
    {
        UserRoleRelationshipsModel::factory()->create([
            'user_uid' => $userUid,
            'user_role_uid' => $roleUid
        ]);
    }
}
