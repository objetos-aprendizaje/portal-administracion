<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LearningObjectGetCoursesTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }
    
}
