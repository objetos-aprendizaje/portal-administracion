<?php

namespace Tests\Unit;

use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsModel;
use App\Models\NotificationsTypesModel;
use App\Models\UserGeneralNotificationsModel;
use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\CoursesStudentsModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\SendUpdateEnrollmentUserCourseNotification;


class JobsSendEnrollmentUserCourseTest extends TestCase
{
    use RefreshDatabase;
    protected $courseStudent;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');

    }


}
