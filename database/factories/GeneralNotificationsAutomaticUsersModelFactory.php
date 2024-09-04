<?php


namespace Database\Factories;


use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\GeneralNotificationsAutomaticUsersModel;

class GeneralNotificationsAutomaticUsersModelFactory extends Factory
{
    protected $model = GeneralNotificationsAutomaticUsersModel::class;

    public function definition()
    {
        return [
            'user_uid' => $this->faker->uuid, // Genera un UID aleatorio para el usuario
            'general_notifications_automatic_uid' => GeneralNotificationsAutomaticModel::factory()->create()->first(), // Genera un UID aleatorio para la notificación
            'is_read' => $this->faker->boolean, // Genera un valor booleano aleatorio
            'uid' => $this->faker->uuid, // Genera un UID aleatorio para la relación
        ];
    }
}
