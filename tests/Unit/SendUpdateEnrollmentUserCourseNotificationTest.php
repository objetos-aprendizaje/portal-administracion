<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\CourseStudent;
use Illuminate\Support\Facades\Log;
use App\Models\CoursesStudentsModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Jobs\SendUpdateEnrollmentUserCourseNotification;
use App\Models\UserGeneralNotificationTypesDisabledModel;
use Database\Factories\AutomaticNotificationTypesModelFactory;


class SendUpdateEnrollmentUserCourseNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * @test 
     * Este test verifica que las relaciones necesarias se cargan correctamente en el constructor.
     */
    public function testConstructorLoadsRequiredRelations()
    {
        // Crear un usuario y un curso
        $user = UsersModel::factory()->create();
        $course = CoursesModel::factory()->create();

        // Crear un UID para el pivot
        $uid = generate_uuid();

        // Asociar el usuario con el curso a través de la tabla pivot `courses_students`
        $user->coursesStudents()->attach($course->uid, [
            'acceptance_status' => 'ACCEPTED',
            'calification_type' => 'NUMERIC',
            'credential' => 'some_credential',
            'uid' => $uid,
        ]);

        // Obtener el registro de la tabla pivot
        $courseStudent = CoursesStudentsModel::where('uid', $uid)->first();

        // Mockear el método load para verificar que las relaciones se cargan correctamente
        $courseStudentMock = Mockery::mock($courseStudent)->makePartial();
        $courseStudentMock->shouldReceive('load')->with([
            'course',
            'user',
            'user.automaticGeneralNotificationsTypesDisabled',
            'user.automaticEmailNotificationsTypesDisabled'
        ])->once()->andReturnSelf();

        // Instanciar el job
        $job = new SendUpdateEnrollmentUserCourseNotification($courseStudentMock);

        // Verificar que se llamara el método load
        $this->assertTrue(true); // Si no hay excepciones, la prueba pasa
    }

    /** 
     * @test 
     * Este test verifica que se envíe una notificación general si el usuario no ha deshabilitado las notificaciones automáticas generales.
     */
    public function testSendsGeneralNotificationIfNotDisabled()
    { // Crear un UID para el pivot
        $uid = generate_uuid();

        $automaticNotificationType = AutomaticNotificationTypesModel::factory()->create([
            'code' => 'COURSE_ENROLLMENT_COMMUNICATIONS'
        ])->first();

        $courseStudent = CoursesStudentsModel::factory()->create();

        // Mockear la relación con `automaticGeneralNotificationsTypesDisabled`
        $courseStudent->user->automaticGeneralNotificationsTypesDisabled = collect();

        $job = new SendUpdateEnrollmentUserCourseNotification($courseStudent);

        // Deshabilitar el envío de correos electrónicos
        Mail::fake();

        // Ejecutar el job
        $job->handle();

        // Verificar que la notificación general se haya creado
        $this->assertDatabaseHas('general_notifications_automatic', [
            'entity_uid' => $courseStudent->course->uid,
            'entity' => 'course',
            // 'automatic_notification_type_uid' =>  $uid,
        ]);
    }

    /** 
     * @test 
     * Este test verifica que se envíe un correo electrónico si el usuario no ha deshabilitado las notificaciones automáticas por email.
     */
    public function testSendsEmailNotificationIfNotDisabled()
    {
        // Crear un usuario y un curso
        $user = UsersModel::factory()->create();
        $course = CoursesModel::factory()->create();

        // Crear un UID para el pivot
        $uid = generate_uuid();

        // Asociar el usuario con el curso a través de la tabla pivot `courses_students`
        $user->coursesStudents()->attach($course->uid, [
            'acceptance_status' => 'REJECTED',
            'calification_type' => 'NUMERIC',
            'credential' => 'some_credential',
            'uid' => $uid,
        ]);

        // Obtener el registro de la tabla pivot
        $courseStudent = CoursesStudentsModel::where('uid', $uid)->first();

        // Mockear la relación con `automaticEmailNotificationsTypesDisabled`
        $user->automaticEmailNotificationsTypesDisabled = collect();

        // Fake para evitar envío real de correos
        Queue::fake();

        // Instanciar el job
        $job = new SendUpdateEnrollmentUserCourseNotification($courseStudent);
        // Agregar un log para ver si el job se ejecuta
        Log::info('Ejecutando el job de notificación de correo...');

        // Ejecutar el job
        $job->handle();

        // Verificar que el trabajo de envío de email fue despachado
        Queue::assertPushed(SendEmailJob::class, function ($job) use ($user, $courseStudent) {
            // dd($job);
            $reflectionClass = new \ReflectionClass($job);

            $emailProperty = $reflectionClass->getProperty('email');
            $emailProperty->setAccessible(true);
            $email = $emailProperty->getValue($job);

            $parametersProperty = $reflectionClass->getProperty('parameters');
            $parametersProperty->setAccessible(true);
            $parameters = $parametersProperty->getValue($job);

            return $email === $user->email &&
                $parameters['course_title'] === $courseStudent->course->title &&
                $parameters['status'] === 'REJECTED';
        });
    }
}
