<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    
    public function testDeleteImageUser(){

        $user = UsersModel::factory()->create([
            'photo_path'=>'mi-imagen.png'
        ]);

        $this->actingAs($user);


        $response = $this->deleteJson('/my_profile/delete_photo');

        // Verifica la respuesta
        $response->assertStatus(200);
            
        $response->assertJson(['message' => 'La foto de perfil se ha eliminado correctamente']);

    }
    
}
