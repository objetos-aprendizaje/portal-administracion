<?php

namespace Database\Seeders;

use App\Models\UsersAccessesModel;
use App\Models\UsersModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class UserAccessesSeeder extends Seeder
{
    protected $users;
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
        $this->users = UsersModel::all()->pluck('uid');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->users->each(function ($user) {
            $visits = rand(1, 100);
            for ($i = 0; $i < $visits; $i++) {
                UsersAccessesModel::factory()->create([
                    'user_uid' => $user,
                    'date' => $this->faker->dateTimeBetween("-2 years", "now")
                ]);
            }
        });
    }
}
