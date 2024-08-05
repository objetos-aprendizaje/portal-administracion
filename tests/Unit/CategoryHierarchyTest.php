<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use PHPUnit\Framework\Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /**
     * @testdox Verifica que la jerarquía se mantiene en la creación de categorías */
    public function testCategoryHierarchy()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            // Crea una categoría padre
            $response = $this->postJson('/cataloging/categories/save_category', [
                'uid' => '42ce6bf8-4p8o-9999-a5e0-b0a4608c1236',
                'name' => 'Categoría nueva',
                'description' => 'Descripción categoría',
                'color' => '#fff',
                'image_path' => UploadedFile::fake()->image('category6.jpg'),
            ]);

            $response->assertStatus(200)
                ->assertJson(['message' => 'Categoría añadida correctamente']);

            // Obtiene la categoría padre recién creada
            $parentCategory = CategoriesModel::where('name', 'Categoría nueva')->first();
            $this->assertNotNull($parentCategory, 'La categoría padre no se creó correctamente.');

            // Crea una categoría hija vinculada a la categoría padre
            $childResponse = $this->postJson('/cataloging/categories/save_category', [
                'name' => 'Categoría Hija',
                'description' => 'Descripción de la categoría hija',
                'color' => '#00ff00',
                'parent_category_uid' => $parentCategory->uid, // Establece la categoría padre
                'image_path' => UploadedFile::fake()->image('child_category.jpg'),
            ]);

            $childResponse->assertStatus(200)
                ->assertJson(['message' => 'Categoría añadida correctamente']);

            // Obtiene la categoría hija recién creada
            $childCategory = CategoriesModel::where('name', 'Categoría Hija')->first();
            $this->assertNotNull($childCategory, 'La categoría hija no se creó correctamente.');

            // Verifica que la categoría hija tenga la categoría padre correcta
            $this->assertEquals($parentCategory->uid, $childCategory->parent_category_uid, 'La categoría hija no está vinculada a la categoría padre correctamente.');

            // Ahora actualiza la categoría hija
            $updateResponse = $this->postJson('/cataloging/categories/save_category', [
                'category_uid' => $childCategory->uid, // Usa el uid de la categoría hija
                'name' => 'Categoría Hija Actualizada',
                'description' => 'Descripción de la categoría hija actualizada',
                'color' => '#0000ff',
                'parent_category_uid' => $parentCategory->uid, // Asegúrate de que siga vinculada a la misma categoría padre
                'image_path' => UploadedFile::fake()->image('updated_child_category.jpg'),
            ]);

            $updateResponse->assertStatus(200)
                ->assertJson(['message' => 'Categoría modificada correctamente']);

            // Verifica que la categoría hija actualizada mantenga la relación con la categoría padre
            $this->assertDatabaseHas('categories', [
                'uid' => $childCategory->uid,
                'name' => 'Categoría Hija Actualizada',
                'parent_category_uid' => $parentCategory->uid,
            ]);
        }
    }
}
