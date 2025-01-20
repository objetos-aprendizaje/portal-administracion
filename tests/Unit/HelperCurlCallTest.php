<?php

namespace Tests\Unit;

use Mockery;
use Exception;
use Tests\TestCase;
use GuzzleHttp\Client;
use App\Models\UsersModel;
use Illuminate\Support\Facades\Auth;


class HelperCurlCallTest extends TestCase
{
    public function testCurlCallReturnsResponse()
    {
        // URL de prueba (asegúrate de que esta URL esté disponible)
        $url = 'https://jsonplaceholder.typicode.com/posts/1'; // Ejemplo de API pública
        $expectedResponse = '{"userId":1,"id":1,"title":"sunt aut facere repellat provident occaecati excepturi optio reprehenderit","body":"quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\nreprehenderit molestiae ut ut quas totam\nnostrum rerum est autem sunt rem eveniet architecto"}';

        // Llamamos al helper
        $response = curlCall($url);

        // Verificamos que la respuesta sea la esperada
        $this->assertJsonStringEqualsJsonString($expectedResponse, $response);
    }

    public function testCurlCallThrowsExceptionOnError()
    {
        // URL de prueba que sabemos que fallará
        $url = 'https://jsonplaceholder.typicode.com/404'; // URL que no existe

        // Verificamos que se lance una excepción
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Error en la petición cURL: \d+/');

        // Llamamos al helper
        curlCall($url);
    }

    public function testGuzzleCallReturnsResponse()
    {
        // URL de prueba (asegúrate de que esta URL esté disponible)
        $url = 'https://jsonplaceholder.typicode.com/posts/1'; // Ejemplo de API pública
        $expectedResponse = '{"userId":1,"id":1,"title":"sunt aut facere repellat provident occaecati excepturi optio reprehenderit","body":"quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\nreprehenderit molestiae ut ut quas totam\nnostrum rerum est autem sunt rem eveniet architecto"}';

        // Llamamos al helper
        $response = guzzle_call($url);

        // Verificamos que la respuesta sea la esperada
        $this->assertJsonStringEqualsJsonString($expectedResponse, $response);
    }

    public function testGuzzleCallThrowsExceptionOnError()
    {
        // URL de prueba que sabemos que fallará
        $url = 'https://jsonplaceholder.typicode.com/404'; // URL que no existe

        // Verificamos que se lance una excepción
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Error en la petición Guzzle: \d+/');

        // Llamamos al helper
        guzzle_call($url);
    }


    public function testCurrentUserReturnsAuthenticatedUser()
    {
        // Creamos un usuario para la prueba
        $user = UsersModel::factory()->create();

        // Simulamos la autenticación del usuario
        Auth::login($user);

        // Llamamos al helper
        $currentUser = currentUser();

        // Verificamos que el usuario actual sea el mismo que el usuario autenticado
        $this->assertEquals($user->id, $currentUser->id);
    }

     public function testCurrentUserReturnsNullWhenNotAuthenticated()
    {
        // Aseguramos que no hay usuario autenticado
        Auth::logout();

        // Llamamos al helper
        $currentUser = currentUser();

        // Verificamos que el resultado sea null
        $this->assertNull($currentUser);
    }



}


