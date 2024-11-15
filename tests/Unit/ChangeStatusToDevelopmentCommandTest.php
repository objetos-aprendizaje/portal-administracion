<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Services\KafkaService;
use App\Models\LmsSystemsModel;
use Illuminate\Support\Facades\DB;
use App\Models\CourseStatusesModel;
use App\Models\CoursesStudentsModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Console\Commands\ChangeStatusToDevelopment;
use App\Models\GeneralNotificationsAutomaticUsersModel;

class ChangeStatusToDevelopmentCommandTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $this->command = new ChangeStatusToDevelopment();

    }

    /**
     * @test
     * Este test verifica que el comando cambia el estado de los cursos a 'DEVELOPMENT'
     * cuando cumplen con las condiciones necesarias.
     */

    public function testChangesCourseStatusPendingDecision()
    {

        $statuscourse = CourseStatusesModel::where('code','INSCRIPTION')->first();

         // Arrange: Create a course in INSCRIPTION status with required attributes
        $course = CoursesModel::factory()->withCourseType()->create([
            'realization_start_date' => now()->subDays(1),
            'realization_finish_date' => now()->addDays(10),
            'min_required_students' => 1,
            'belongs_to_educational_program' => 0,
            'course_status_uid' => $statuscourse->uid,
        ]);

        // Act: Execute the command using Artisan
        Artisan::call('app:change-status-to-development');

        // Assert: Check if the course status has changed to DEVELOPMENT
        $course->refresh(); // Refresh the course instance to get updated data
        $this->assertEquals('PENDING_DECISION', $course->status->code);
    }


    public function testChangesCoursesStatusDevelopment()
    {

        $lmsystem = LmsSystemsModel::factory()->create([
            'identifier' => 'identifier'
        ]);
        $statuscourse = CourseStatusesModel::where('code','ENROLLING')->first();

        CoursesModel::factory()->withCourseType()->create([
            'title' => 'New Course example',
            'realization_start_date' => now()->subDays(1),
            'realization_finish_date' => now()->addDays(10),
            'min_required_students' => 0,
            'belongs_to_educational_program' => 0,
            'course_status_uid' => $statuscourse->uid,
            'identifier' => 'identifier',
            'lms_system_uid' => $lmsystem->uid,
        ]);
        $course = CoursesModel::where('title','New Course example')->first();

        $student1 = UsersModel::factory()->create(['email' => 'student1@example.com']);
        $student2 = UsersModel::factory()->create(['email' => 'student2@example.com']);


        CoursesStudentsModel::factory()->create([
            'uid' => generate_uuid(),
            'user_uid' => $student1->uid,
            'course_uid' => $course->uid,
        ]);

        CoursesStudentsModel::factory()->create([
            'uid' => generate_uuid(),
            'user_uid' => $student2->uid,
            'course_uid' => $course->uid,
        ]);


        Artisan::call('app:change-status-to-development');

        $course->refresh();
        $this->assertEquals('DEVELOPMENT', $course->status->code);
    }

     /** @test */
     public function testSendsEmailNotificationsEnrolledUsers()
     {
         // Arrange: Create a course and students
         $statusCourse = CourseStatusesModel::where('code', 'ENROLLING')->first();
         $course = CoursesModel::factory()->withCourseType()->create([
             'title' => 'New Course Example',
             'realization_finish_date' => now()->addDays(10),
             'course_status_uid' => $statusCourse->uid,
         ]);

         // Create two users for the course
         $student1 = UsersModel::factory()->create(['email' => 'student1@example.com']);
         $student2 = UsersModel::factory()->create(['email' => 'student2@example.com']);

        CoursesStudentsModel::factory()->create([
            'uid' => generate_uuid(),
            'user_uid' => $student1->uid,
            'course_uid' => $course->uid,
        ]);

        CoursesStudentsModel::factory()->create([
            'uid' => generate_uuid(),
            'user_uid' => $student2->uid,
            'course_uid' => $course->uid,
        ]);


         // Fake the Queue to prevent actual email sending
         Queue::fake();

         // Act: Use Reflection to call the private method
         $command = new ChangeStatusToDevelopment();

         $reflection = new \ReflectionClass($command);
         $method = $reflection->getMethod('sendEmailsNotificationsUsersEnrolled');
         $method->setAccessible(true); // Make the method accessible

         // Call the private method with the course instance
         $method->invoke($command, $course);

         // Assert: Check that two email jobs were dispatched
         Queue::assertPushed(SendEmailJob::class, 2);


     }

      /** @test */
    public function testSavesGeneralNotifications()
    {
        // Arrange: Create necessary models and relationships
        $course = CoursesModel::factory()->withCourseType()->withCourseStatus()->create([
            'title' => 'New Course Example',
            'realization_finish_date' => now()->addDays(10),
        ]);

        // Create two users for the course
        $student1 = UsersModel::factory()->create(['email' => 'student1@example.com']);
        $student2 = UsersModel::factory()->create(['email' => 'student2@example.com']);

        CoursesStudentsModel::factory()->create([
            'uid' => generate_uuid(),
            'user_uid' => $student1->uid,
            'course_uid' => $course->uid,
        ]);

        CoursesStudentsModel::factory()->create([
            'uid' => generate_uuid(),
            'user_uid' => $student2->uid,
            'course_uid' => $course->uid,
        ]);

        // Create an automatic notification type
        $notificationType = AutomaticNotificationTypesModel::factory()->create([
            'code' => 'COURSE_ENROLLMENT_COMMUNICATIONS'
        ]);

        // Act: Use Reflection to call the private method
        $command = new ChangeStatusToDevelopment();

        // Mock the filterUsersNotification method to return both students
        $reflection = new \ReflectionClass($command);

        // Mocking filterUsersNotification using a closure
        $methodFilter = $reflection->getMethod('filterUsersNotification');
        $methodFilter->setAccessible(true);

        // Use reflection to access and modify the command's behavior
        $studentsFiltered = collect([$student1, $student2]);

        // Call the private method saveGeneralNotificationsUsers using reflection
        $methodSave = $reflection->getMethod('saveGeneralNotificationsUsers');
        $methodSave->setAccessible(true);

        // Call the method with the course instance
        $methodSave->invoke($command, $course);

        // Assert: Check that general notifications were saved for each student
        $this->assertCount(2, GeneralNotificationsAutomaticUsersModel::all());

        foreach (GeneralNotificationsAutomaticUsersModel::all() as $notificationUser) {
            $this->assertTrue(in_array($notificationUser->user_uid, [$student1->uid, $student2->uid]));
            $this->assertEquals($notificationUser->general_notifications_automatic_uid, GeneralNotificationsAutomaticModel::first()->uid);
        }

    }

    /** @test */
    public function testFiltersUsersBasedOnEmailNotifications()
    {
        // Arrange: Create users with different notification settings
        $user1 = UsersModel::factory()->create();
        $user2 = UsersModel::factory()->create();

        // Simulate the automaticEmailNotificationsTypesDisabled property
        $user1->automaticEmailNotificationsTypesDisabled = collect([
            (object) ['code' => 'COURSE_ENROLLMENT_COMMUNICATIONS'],
        ]);

        $user2->automaticEmailNotificationsTypesDisabled = collect([]);

        // Create a collection of users
        $users = collect([$user1, $user2]);

        // Act: Use Reflection to call the private method
        $command = new ChangeStatusToDevelopment();

        $reflection = new \ReflectionClass($command);
        $methodFilter = $reflection->getMethod('filterUsersNotification');
        $methodFilter->setAccessible(true);

        // Call the method with the users collection and "email" type
        $filteredUsers = $methodFilter->invoke($command, $users, 'email');

        // Assert: Check that only user2 is returned
        $this->assertCount(1, $filteredUsers);
        $this->assertEquals($user2->uid, $filteredUsers->first()->uid);
    }

     /** @test */
     public function testFiltersUsersBasedOnGeneralNotifications()
     {
         // Arrange: Create users with different notification settings
         $user1 = UsersModel::factory()->create();
         $user2 = UsersModel::factory()->create();

         // Simulate the automaticGeneralNotificationsTypesDisabled property
         $user1->automaticGeneralNotificationsTypesDisabled = collect([
             (object) ['code' => 'COURSE_ENROLLMENT_COMMUNICATIONS'],
         ]);

         $user2->automaticGeneralNotificationsTypesDisabled = collect([]);

         // Create a collection of users
         $users = collect([$user1, $user2]);

         // Act: Use Reflection to call the private method
         $command = new ChangeStatusToDevelopment();

         $reflection = new \ReflectionClass($command);
         $methodFilter = $reflection->getMethod('filterUsersNotification');
         $methodFilter->setAccessible(true);

         // Call the method with the users collection and "general" type
         $filteredUsers = $methodFilter->invoke($command, $users, 'general');

         // Assert: Check that only user2 is returned
         $this->assertCount(1, $filteredUsers);
         $this->assertEquals($user2->uid, $filteredUsers->first()->uid);
     }


}
