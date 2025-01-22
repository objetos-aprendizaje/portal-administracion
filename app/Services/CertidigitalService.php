<?php

namespace App\Services;

use App\Models\CertidigitalAchievementsModel;
use App\Models\CertidigitalActivitiesModel;
use App\Models\CertidigitalAssesmentsModel;
use App\Models\CertidigitalCredentialsModel;
use App\Models\CertidigitalLearningOutcomesModel;
use App\Models\CourseGlobalCalificationsModel;
use App\Models\CoursesModel;
use App\Models\CoursesStudentsModel;
use App\Models\CoursesTeachersModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramsStudentsModel;
use Exception;
use Illuminate\Support\Facades\Http;

class CertidigitalService
{

    public function createUpdateEducationalProgramCredential($educationalProgramUid)
    {
        $educationalProgram = EducationalProgramsModel::where('uid', $educationalProgramUid)->with([
            "courses",
            "courses.blocks.learningResults",
            "courses.blocks.subBlocks.elements.subElements",
            'certidigitalCredential'
        ])->first();

        $credential = $this->createUpdateCoursesCredentials($educationalProgram->courses, $educationalProgram->name, $educationalProgram->description,  $educationalProgram->certidigitalCredential);

        if ($credential) {
            $educationalProgram->certidigital_credential_uid = $credential;
            $educationalProgram->save();
        }
    }

    public function createUpdateCourseTeacherCredential($courseUid)
    {
        $course = CoursesModel::where('uid', $courseUid)->with([
            "blocks",
            "certidigitalTeacherCredential.activities",
        ])->first();

        // Si ya existe la credencial, se vacía todo lo que hay en ella
        if ($course->certidigitalTeacherCredential) {
            $this->cleanCredential($course->certidigitalTeacherCredential);
        }

        $activities = [];
        foreach ($course->blocks as $block) {
            $activities[] = $this->createActivity($block->name);
        }

        // Si ya existe la credencial, se vinculan las actividades a ella.
        // Si no existe, se crea la credencial y se vinculan las actividades a ella.
        if ($course->certidigitalTeacherCredential) {
            $this->vinculateActivitiesToCredential($activities, $course->certidigitalTeacherCredential);
        } else {
            $credential = $this->createCredential($course->title, $course->description, null, $activities);
            $course->certidigital_teacher_credential_uid = $credential['ocbid'];
            $course->save();
        }
    }

    private function vinculateActivitiesToCredential($activities, $credential)
    {
        $generalOptions = app('general_options');

        // Quitar actividades
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials/{$credential->id}/performed?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $data = [
            "oid" => array_column($activities, "oid"),
            "singleOid" => 0
        ];

        $this->sendRequest($endpoint, $data, false);

        // Vincular actividades
        CertidigitalActivitiesModel::whereIn('uid', array_column($activities, 'ocbid'))->update(['certidigital_credential_uid' => $credential->uid]);
    }

    public function createUpdateCourseCredential($courseUid)
    {
        $course = CoursesModel::where('uid', $courseUid)->with([
            "educational_program",
            "embeddings",
            "blocks.learningResults",
            "blocks.subBlocks.elements.subElements",
            "certidigitalCredential",
        ])->first();

        $credential = $this->createUpdateCoursesCredentials([$course], $course->title, $course->description, $course->certidigitalCredential);

        if ($credential) {
            $course->certidigital_credential_uid = $credential;
            $course->save();
        }
    }

    public function emissionsCredentialEducationalProgram($educationalProgramUid, $studentsUids = null)
    {
        $uidsCoursesEducationalProgram = CoursesModel::where('educational_program_uid', $educationalProgramUid)->pluck('uid')->toArray();

        $educationalProgram = $this->getEducationalProgramWithRelations($educationalProgramUid, $uidsCoursesEducationalProgram, $studentsUids);

        $allLearningResults = $this->getAllLearningResults($educationalProgram);

        $recipients = $this->prepareRecipients($educationalProgram, $allLearningResults);

        $finalStructure = [
            "recipients" => $recipients
        ];

        $this->emitCredentialsEducationalProgram($educationalProgram, $finalStructure);
    }

    private function getEducationalProgramWithRelations($educationalProgramUid, $uidsCoursesEducationalProgram, $studentsUids)
    {
        return EducationalProgramsModel::where('uid', $educationalProgramUid)->with([
            'certidigitalCredential',
            'courses',
            'courses.certidigitalAssesments',
            'courses.blocks.learningResults.certidigitalAssesments' => function ($query) use ($uidsCoursesEducationalProgram) {
                $query->whereIn('course_block_uid', function ($subQuery) use ($uidsCoursesEducationalProgram) {
                    $subQuery->select('uid')
                        ->from('course_blocks')
                        ->whereIn('course_uid', $uidsCoursesEducationalProgram);
                });
            },
            'students' => function ($query) use ($studentsUids) {
                if ($studentsUids) {
                    $query->whereIn('user_uid', $studentsUids);
                }
            },
            'students.courseGlobalCalifications' => function ($query) use ($uidsCoursesEducationalProgram) {
                $query->whereIn('course_uid', $uidsCoursesEducationalProgram);
            },
            'students.courseBlocksLearningResultsCalifications' => function ($query) use ($uidsCoursesEducationalProgram) {
                $query->whereIn('course_block_uid', function ($subQuery) use ($uidsCoursesEducationalProgram) {
                    $subQuery->select('uid')
                        ->from('course_blocks')
                        ->whereIn('course_uid', $uidsCoursesEducationalProgram);
                });
            },
            'students.courseLearningResultCalifications' => function ($query) use ($uidsCoursesEducationalProgram) {
                $query->whereIn('course_uid', $uidsCoursesEducationalProgram);
            }
        ])->first();
    }

    private function getAllLearningResults($educationalProgram)
    {
        return $educationalProgram->courses->flatMap(function ($course) {
            return $course->blocks->flatMap(function ($block) {
                return $block->learningResults;
            })->unique("uid");
        });
    }

    private function prepareRecipients($educationalProgram, $allLearningResults)
    {
        $recipients = [];
        foreach ($educationalProgram->students as $student) {
            $fields = $this->prepareFieldsForStudent($student, $educationalProgram, $allLearningResults);
            $recipients[] = [
                "entities" => [
                    [
                        "fields" => $fields
                    ]
                ]
            ];
        }
        return $recipients;
    }

    private function prepareFieldsForStudent($student, $educationalProgram, $allLearningResults)
    {
        $fields = $this->getBasicDataEmissionCredential($student, $educationalProgram->certidigitalCredential->uid);

        foreach ($educationalProgram->courses as $course) {
            $fields = array_merge($fields, $this->getCourseFields($student, $course, $educationalProgram, $allLearningResults));
        }

        return $fields;
    }

    private function getCourseFields($student, $course, $educationalProgram, $allLearningResults)
    {
        $fields = [];

        foreach ($course->blocks as $block) {
            foreach ($block->learningResults as $learningResult) {
                $calification = $student->courseBlocksLearningResultsCalifications->filter(function ($calification) use ($learningResult, $block) {
                    return $calification->course_block_uid === $block->uid && $calification->learning_result_uid === $learningResult->uid;
                })->first();

                if ($calification) {
                    $certidigitalAssesment = $course->certidigitalAssesments->filter(function ($assesment) use ($learningResult, $block) {
                        return $assesment->learning_result_uid === $learningResult->uid && $assesment->course_block_uid === $block->uid;
                    })->first();

                    $fields[] = [
                        'fieldPathIdentifier' => "{#{$educationalProgram->certidigitalCredential->uid}}.ASM{{$certidigitalAssesment->uid}}.grade.noteLiteral(es)",
                        'value' => $calification->calification_info,
                    ];
                }
            }
        }

        foreach ($allLearningResults as $learningResult) {
            $calification = $student->courseLearningResultCalifications->filter(function ($calification) use ($learningResult, $course) {
                return $calification->learning_result_uid === $learningResult->uid && $calification->course_uid === $course->uid;
            })->first();

            if ($calification) {
                $assesmentLearningResult = $course->certidigitalAssesments->filter(function ($assesment) use ($learningResult, $course) {
                    return $assesment->learning_result_uid === $learningResult->uid && !$assesment->course_block_uid && $assesment->course_uid === $course->uid;
                })->first();

                $fields[] = [
                    'fieldPathIdentifier' => "{#{$educationalProgram->certidigitalCredential->uid}}.ASM{{$assesmentLearningResult->uid}}.grade.noteLiteral(es)",
                    'value' => $calification->calification_info,
                ];
            }
        }

        $assesmentGlobal = $course->certidigitalAssesments->filter(function ($assesment) use ($course) {
            return !$assesment->learning_result_uid && !$assesment->course_block_uid && $assesment->course_uid === $course->uid;
        })->first();

        $studentCourseGlobalCalifications = $student->courseGlobalCalifications->filter(function ($calification) use ($course) {
            return $calification->course_uid === $course->uid;
        })->first();

        $fields[] = [
            'fieldPathIdentifier' => "{#{$educationalProgram->certidigitalCredential->uid}}.ASM{{$assesmentGlobal->uid}}.grade.noteLiteral(es)",
            'value' => $studentCourseGlobalCalifications->calification_info,
        ];

        return $fields;
    }

    public function emissionCredentialsCourse($courseUid, $studentsUids = null)
    {
        $course = $this->getCourseWithRelations($courseUid, $studentsUids);

        $allLearningResults = $this->getAllLearningResultsFromCourse($course);

        $recipients = $this->prepareRecipientsForCourse($course, $allLearningResults);

        $finalStructure = [
            "recipients" => $recipients
        ];

        // Todo: Enviar a Certidigital
        $this->emitCredentialsCourse($course, $finalStructure);
    }

    private function getCourseWithRelations($courseUid, $studentsUids)
    {
        return CoursesModel::where('uid', $courseUid)->with([
            'certidigitalCredential',
            'certidigitalAssesments',
            'blocks.learningResults.certidigitalAssesments' => function ($query) use ($courseUid) {
                $query->whereIn('course_block_uid', function ($subQuery) use ($courseUid) {
                    $subQuery->select('uid')
                        ->from('course_blocks')
                        ->where('course_uid', $courseUid);
                });
            },
            'students' => function ($query) use ($studentsUids, $courseUid) {
                if ($studentsUids) {
                    $query->whereIn('user_uid', $studentsUids);
                }
                $query->addSelect([
                    'global_calification_info' => CourseGlobalCalificationsModel::selectRaw('calification_info')
                        ->whereColumn('user_uid', 'users.uid')
                        ->where('course_uid', $courseUid)
                ]);
            },
            'students.courseBlocksLearningResultsCalifications' => function ($query) use ($courseUid) {
                $query->whereIn('course_block_uid', function ($subQuery) use ($courseUid) {
                    $subQuery->select('uid')
                        ->from('course_blocks')
                        ->where('course_uid', $courseUid);
                });
            },
            'students.courseLearningResultCalifications' => function ($query) use ($courseUid) {
                $query->where('course_uid', $courseUid);
            }
        ])->first();
    }

    private function getAllLearningResultsFromCourse($course)
    {
        return $course->blocks->flatMap(function ($block) {
            return $block->learningResults;
        })->unique("uid");
    }

    private function prepareRecipientsForCourse($course, $allLearningResults)
    {
        $recipients = [];
        foreach ($course->students as $student) {
            $fields = $this->prepareFieldsForStudentInCourse($student, $course, $allLearningResults);
            $recipients[] = [
                "entities" => [
                    [
                        "fields" => $fields
                    ]
                ]
            ];
        }
        return $recipients;
    }

    private function prepareFieldsForStudentInCourse($student, $course, $allLearningResults)
    {
        $fields = $this->getBasicDataEmissionCredential($student, $course->certidigitalCredential->uid);

        foreach ($course->blocks as $block) {
            $fields = array_merge($fields, $this->getBlockFields($student, $block, $course));
        }

        foreach ($allLearningResults as $learningResult) {
            $fields = array_merge($fields, $this->getLearningResultFields($student, $learningResult, $course));
        }

        $fields = array_merge($fields, $this->getGlobalCalificationFields($student, $course));

        return $fields;
    }

    private function getBlockFields($student, $block, $course)
    {
        $fields = [];
        foreach ($block->learningResults as $learningResult) {
            $calification = $student->courseBlocksLearningResultsCalifications->filter(function ($calification) use ($learningResult, $block) {
                return $calification->course_block_uid === $block->uid && $calification->learning_result_uid === $learningResult->uid;
            })->first();

            if ($calification) {
                $certidigitalAssesment = $course->certidigitalAssesments->filter(function ($assesment) use ($learningResult, $block) {
                    return $assesment->learning_result_uid === $learningResult->uid && $assesment->course_block_uid === $block->uid;
                })->first();

                $fields[] = [
                    'fieldPathIdentifier' => "{#{$course->certidigitalCredential->uid}}.ASM{{$certidigitalAssesment->uid}}.grade.noteLiteral(es)",
                    'value' => $calification->calification_info,
                ];
            }
        }
        return $fields;
    }

    private function getLearningResultFields($student, $learningResult, $course)
    {
        $fields = [];
        $calification = $student->courseLearningResultCalifications->filter(function ($calification) use ($learningResult) {
            return $calification->learning_result_uid === $learningResult->uid;
        })->first();

        if ($calification) {
            $assesmentLearningResult = $course->certidigitalAssesments->filter(function ($assesment) use ($learningResult) {
                return $assesment->learning_result_uid === $learningResult->uid && !$assesment->course_block_uid;
            })->first();

            $fields[] = [
                'fieldPathIdentifier' => "{#{$course->certidigitalCredential->uid}}.ASM{{$assesmentLearningResult->uid}}.grade.noteLiteral(es)",
                'value' => $calification->calification_info,
            ];
        }
        return $fields;
    }

    private function getGlobalCalificationFields($student, $course)
    {
        $fields = [];
        $assesmentGlobal = $course->certidigitalAssesments->filter(function ($assesment) {
            return !$assesment->learning_result_uid && !$assesment->course_block_uid;
        })->first();

        $fields[] = [
            'fieldPathIdentifier' => "{#{$course->certidigitalCredential->uid}}.ASM{{$assesmentGlobal->uid}}.grade.noteLiteral(es)",
            'value' => $student->global_calification_info,
        ];

        return $fields;
    }

    public function emissionCredentialsTeacherCourse($courseUid, $teacherUids)
    {
        $course = CoursesModel::where("uid", $courseUid)->with([
            "certidigitalTeacherCredential.activities",
            "blocks",
            "teachers" => function ($query) use ($teacherUids) {
                $query->whereIn("user_uid", $teacherUids);
            }
        ])->first();

        $recipients = [];

        foreach ($course->teachers as $teacher) {
            $basicDataTeacher = $this->getBasicDataEmissionCredential($teacher, $course->certidigitalTeacherCredential->uid);
            $recipients[] = [
                "entities" => [
                    [
                        "fields" => $basicDataTeacher
                    ]
                ]
            ];
        }

        $data = [
            "recipients" => $recipients
        ];

        $emissions = $this->emitCredentialsRequest($course->certidigitalTeacherCredential->id, $data);

        foreach ($emissions as $emission) {
            // Buscar el docente
            $teacher = $course->teachers->filter(function ($teacher) use ($emission) {
                return $teacher->email === $emission['address'];
            })->first();

            // Actualizar el docente con la emisión
            CoursesTeachersModel::where('user_uid', $teacher->uid)
                ->where('course_uid', $course->uid)
                ->update([
                    'emissions_block_id' => $emission['emissionsBlockId'],
                    'emissions_block_uuid' => $emission['uuid'],
                ]);
        }
    }

    public function sendCourseCredentials($coursesUids, $studentUid = [])
    {
        $courses = CoursesModel::whereIn('uid', $coursesUids)->with([
            'students' => function ($query) use ($studentUid) {
                $query->where('user_uid', $studentUid);
            },
        ])->get();

        $emissionsBlockUuids = [];
        foreach ($courses as $course) {
            foreach ($course->students as $student) {
                $emissionsBlockUuids[] = $student->course_student_info->emissions_block_uuid;
            }
        }

        $this->sendEmissionsRequest($emissionsBlockUuids);

        foreach ($courses as $course) {
            foreach ($course->students as $student) {
                $student->course_student_info->credential_sent = true;
                $student->course_student_info->save();
            }
        }
    }

    public function sendCredentialsEducationalPrograms($educationalProgramsUids, $studentUid)
    {
        $educationalPrograms = EducationalProgramsModel::whereIn("uid", $educationalProgramsUids)->with([
            "students" => function ($query) use ($studentUid) {
                $query->where("user_uid", $studentUid);
            }
        ])->get();

        $emissionsBlockUuids = [];
        foreach ($educationalPrograms as $educationalProgram) {
            foreach ($educationalProgram->students as $student) {
                $emissionsBlockUuids[] = $student->educational_program_student_info->emissions_block_uuid;
            }
        }

        $this->sendEmissionsRequest($emissionsBlockUuids);

        foreach ($educationalPrograms as $educationalProgram) {
            foreach ($educationalProgram->students as $student) {
                $student->educational_program_student_info->credential_sent = true;
                $student->educational_program_student_info->save();
            }
        }
    }

    public function sealCoursesCredentials($coursesUids, $studentUid)
    {
        $courses = CoursesModel::whereIn('uid', $coursesUids)->with([
            'students' => function ($query) use ($studentUid) {
                $query->where('user_uid', $studentUid)->first();
            },
        ])->get();

        $emissionsBlockUuids = [];
        foreach ($courses as $course) {
            foreach ($course->students as $student) {
                $emissionsBlockUuids[] = $student->course_student_info->emissions_block_uuid;
            }
        }

        $this->sealEmissionsRequest($emissionsBlockUuids);

        foreach ($courses as $course) {
            foreach ($course->students as $student) {
                $student->course_student_info->credential_sealed = true;
                $student->course_student_info->save();
            }
        }
    }

    public function sealEducationalProgramsCredentials($educationalProgramsUids, $studentUid)
    {
        $educationalPrograms = EducationalProgramsModel::whereIn("uid", $educationalProgramsUids)->with([
            "students" => function ($query) use ($studentUid) {
                $query->where("user_uid", $studentUid)->first();
            }
        ])->get();

        $emissionsBlockUuids = [];
        foreach ($educationalPrograms as $educationalProgram) {
            foreach ($educationalProgram->students as $student) {
                $emissionsBlockUuids[] = $student->educational_program_student_info->emissions_block_uuid;
            }
        }

        $this->sealEmissionsRequest($emissionsBlockUuids);

        foreach ($educationalPrograms as $educationalProgram) {
            foreach ($educationalProgram->students as $student) {
                $student->educational_program_student_info->credential_sealed = true;
                $student->educational_program_student_info->save();
            }
        }
    }

    public function sendCourseCredentialsTeachers($courseUid, $teacherUids)
    {
        $course = $this->getCourseWithTeachers($courseUid, $teacherUids);

        $emissionsBlockUuids = [];
        foreach ($course->teachers as $teacher) {
            $emissionsBlockUuids[] = $teacher->pivot->emissions_block_uuid;
        }

        $this->sendEmissionsRequest($emissionsBlockUuids);

        foreach ($course->teachers as $teacher) {
            $teacher->pivot->credential_sent = true;
            $teacher->pivot->save();
        }
    }

    public function sealCourseCredentialsTeachers($courseUid, $teacherUids)
    {
        $course = $this->getCourseWithTeachers($courseUid, $teacherUids);

        $emissionsBlockUuids = [];
        foreach ($course->teachers as $teacher) {
            $emissionsBlockUuids[] = $teacher->pivot->emissions_block_uuid;
        }

        $this->sealEmissionsRequest($emissionsBlockUuids);

        foreach ($course->teachers as $teacher) {
            $teacher->pivot->credential_sealed = true;
            $teacher->pivot->save();
        }
    }

    private function getCourseWithTeachers($courseUid, $teacherUids)
    {
        return CoursesModel::where("uid", $courseUid)->with([
            "teachers" => function ($query) use ($teacherUids) {
                $query->where("user_uid", $teacherUids);
            }
        ])->first();
    }

    private function sendEmissionsRequest($emissionsBlockUuids)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-admin/api/v1/emissions/send";

        $this->sendRequest($endpoint, $emissionsBlockUuids, false);
    }

    private function sealEmissionsRequest($emissionsBlockUuids)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-admin/api/v1/emissions/seal";

        $data = [
            "uuidList" => $emissionsBlockUuids,
            "issuingCenterId" => $generalOptions['certidigital_center_id']
        ];

        $this->sendRequest($endpoint, $data, false);
    }

    private function createUpdateCoursesCredentials($courses, $titleCredential = "", $descriptionCredential = "", $certidigitalCredential = null)
    {
        $achievementsCourses = [];

        foreach ($courses as $course) {
            $course = $this->reloadCourseWithRelations($course->uid);

            if ($certidigitalCredential) {
                $this->cleanCredential($certidigitalCredential);
            }

            $learningResults = $this->getAllLearningResultsCourse($course);
            $assesmentCourse = $this->createAssesment($course->title, $course->uid);
            $achievementsLearningResults = $this->createAchievementsForLearningResults($learningResults, $course);

            $activities = $this->createActivitiesForCourse($course);

            $achievementsCourses[] = $this->createAchievement($course->title, $achievementsLearningResults, null, [$assesmentCourse], $activities);
        }

        if (!$certidigitalCredential) {
            $credential = $this->createCredential($titleCredential, $descriptionCredential, $achievementsCourses);
            return $credential['ocbid'];
        } else {
            $this->vinculateAchievementsToCredential($achievementsCourses, $certidigitalCredential);
            return null;
        }
    }

    private function reloadCourseWithRelations($courseUid)
    {
        return CoursesModel::where('uid', $courseUid)->with([
            "educational_program",
            "embeddings",
            "blocks.learningResults",
            "blocks.subBlocks.elements.subElements",
            "certidigitalCredential",
        ])->first();
    }

    private function createAchievementsForLearningResults($learningResults, $course)
    {
        $achievementsLearningResults = [];
        foreach ($learningResults as $learningResult) {
            $learningOutcome = $this->createLearningOutcome($learningResult['name'], null);
            $assesment = $this->createAssesment($learningResult['name'], $course->uid, null, $learningResult['uid']);

            $achievementsBlocks = $this->createAchievementsForBlocks($course, $learningResult);

            $achievementGlobal = $this->createAchievement($learningResult['name'], $achievementsBlocks, [$learningOutcome], [$assesment]);
            $achievementsLearningResults[] = $achievementGlobal;
        }
        return $achievementsLearningResults;
    }

    private function createAchievementsForBlocks($course, $learningResult)
    {
        $achievementsBlocks = [];
        foreach ($course->blocks as $block) {
            foreach ($block->learningResults as $learningResultBlock) {
                if ($learningResultBlock->uid == $learningResult['uid']) {
                    $assesmentLearningResultBlock = $this->createAssesment($learningResultBlock->name, $course->uid, $block->uid, $learningResultBlock->uid);
                    $achievementLearningResultBlock = $this->createAchievement($learningResultBlock->name . ' B:' . $block->name, null, null, [$assesmentLearningResultBlock]);
                    $achievementsBlocks[] = $achievementLearningResultBlock;
                }
            }
        }
        return $achievementsBlocks;
    }

    private function createActivitiesForCourse($course)
    {
        $activities = [];
        foreach ($course->blocks as $block) {
            $activities[] = $this->createActivity($block->name);
        }
        return $activities;
    }

    private function createCredential($name, $description = null, $achievements = null, $activities = null)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $name,
                        "language" => "es"
                    ]
                ]
            ],
            "validFrom" => now()->toIso8601String(),
            "validUntil" => null,
            "type" => [
                "uri" => "http://data.europa.eu/snb/credential/e34929035b",
                "targetName" => [
                    "contents" => [
                        [
                            "content" => "Genérica",
                            "language" => "es",
                            "format" => "text/plain"
                        ]
                    ]
                ],
                "targetDescription" => null,
                "targetFrameworkURI" => "http://data.europa.eu/snb/credential/25831c2",
                "targetNotation" => null,
                "targetFramework" => null
            ],
        ];

        if ($description) {
            $data['description'] = [
                "contents" => [
                    [
                        "content" => $description,
                        "language" => "es"
                    ]
                ]
            ];
        }

        if ($achievements) {
            $data['relAchieved'] = [
                "oid" => array_column($achievements, "oid")
            ];
        }

        if ($activities) {
            $data['relPerformed'] = [
                "oid" => array_column($activities, "oid")
            ];
        }

        $response = $this->sendRequest($endpoint, $data, true);

        CertidigitalCredentialsModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $name
        ]);

        if ($achievements) {
            CertidigitalAchievementsModel::whereIn('uid', array_column($achievements, "ocbid"))->update(['certidigital_credential_uid' => $response->json()['ocbid']]);
        }

        if ($activities) {
            CertidigitalActivitiesModel::whereIn('uid', array_column($activities, "ocbid"))->update(['certidigital_credential_uid' => $response->json()['ocbid']]);
        }

        return $response->json();
    }

    private function cleanCredential($credential)
    {
        $this->cleanElementsAchievement($credential->achievements);

        // Vaciar la credencial
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials/{$credential->id}/achieved?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $data = [
            "oid" => []
        ];
        $this->sendRequest($endpoint, $data, false);

        // Quitar actividades
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials/{$credential->id}/performed?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $data = [
            "oid" => [],
            "singleOid" => 0
        ];

        $this->sendRequest($endpoint, $data, false);
    }

    private function vinculateAchievementsToCredential($achievements, $certidigitalCredential)
    {
        $generalOptions = app('general_options');
        $certidigitalCredentialOid = $certidigitalCredential->id;
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials/{$certidigitalCredentialOid}/achieved?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $data = [
            "oid" => array_column($achievements, "oid")
        ];

        CertidigitalAchievementsModel::whereIn('uid', array_column($achievements, 'ocbid'))->update(['certidigital_credential_uid' => $certidigitalCredential->uid]);
        $this->sendRequest($endpoint, $data, false);
    }

    private function emitCredentialsEducationalProgram($educationalProgram, $data)
    {
        $emissions = $this->emitCredentialsRequest($educationalProgram->certidigitalCredential->id, $data);

        foreach ($emissions as $emission) {
            // Buscar el estudiante
            $student = $educationalProgram->students->filter(function ($student) use ($emission) {
                return $student->email === $emission['address'];
            })->first();

            // Actualizar el estudiante con la emisión
            EducationalProgramsStudentsModel::where('user_uid', $student->uid)
                ->where('educational_program_uid', $educationalProgram->uid)
                ->update([
                    'emissions_block_id' => $emission['emissionsBlockId'],
                    'emissions_block_uuid' => $emission['uuid'],
                ]);
        }
    }

    private function emitCredentialsCourse($course, $data)
    {
        $emissions = $this->emitCredentialsRequest($course->certidigitalCredential->id, $data);
        foreach ($emissions as $emission) {
            // Buscar el estudiante
            $student = $course->students->filter(function ($student) use ($emission) {
                return $student->email === $emission['address'];
            })->first();

            // Actualizar el estudiante con la emisión
            CoursesStudentsModel::where('user_uid', $student->uid)
                ->where('course_uid', $course->uid)
                ->update([
                    'emissions_block_id' => $emission['emissionsBlockId'],
                    'emissions_block_uuid' => $emission['uuid'],
                ]);
        }
    }

    private function emitCredentialsRequest($credentialId, $data)
    {
        $generalOptions = app('general_options');

        // Emisión del bloque
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials/{$credentialId}/issuecredentials?issuingCenterId={$generalOptions['certidigital_center_id']}&locale=es";
        $response = $this->sendRequest($endpoint, $data, false);

        return $response->json();
    }

    private function getBasicDataEmissionCredential($student, $certidigitalCredentialUid)
    {
        $fields = [
            'givenName' => $student->first_name,
            'familyName' => $student->last_name,
            'primaryDeliveryAddress' => $student->email,
        ];

        $data = [];
        foreach ($fields as $field => $value) {
            $data[] = [
                'fieldPathIdentifier' => "{#{$certidigitalCredentialUid}}.REC.{$field}",
                'value' => $value,
            ];
        }

        return $data;
    }

    private function cleanElementsAchievement($achievements)
    {
        foreach ($achievements as $achievement) {
            // Desvincular todo del achievement
            $this->desvinculateAllElementsAchievement($achievement);

            if (isset($achievement->activities) && count($achievement->activities)) {
                $this->deleteActivities($achievement->activities);
            }

            if (isset($achievement->learningOutcomes) && count($achievement->learningOutcomes)) {
                $this->deleteLearningOutcomes($achievement->learningOutcomes);
            }

            if (isset($achievement->assesments) && count($achievement->assesments)) {
                $this->deleteAssesments($achievement->assesments);
            }

            if ($achievement->subAchievements) {
                $this->deleteAchievements($achievement->subAchievements);
            }

            $this->deleteAchievements([$achievement]);
        }
    }

    private function desvinculateAllElementsAchievement($achievement)
    {
        $achievementOid = $achievement['id'];
        $generalOptions = app('general_options');

        $data = [
            "oid" => []
        ];

        // TODO eliminación de evaluaciones

        // Activities
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements/$achievementOid/influencedBy?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $this->sendRequest($endpoint, $data, false);

        // Sublogros
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements/$achievementOid/subAchievements?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $this->sendRequest($endpoint, $data, false);

        // learningOutcomes
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements/$achievementOid/learningOutcomes?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $this->sendRequest($endpoint, $data, false);
    }

    private function vinculateAssesmentToAchievement($assesment, $achievement)
    {
        $generalOptions = app('general_options');
        $achievementOid = $achievement['oid'];
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements/{$achievementOid}/provenBy?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $data = [
            "oid" => [$assesment['oid']]
        ];

        $this->sendRequest($endpoint, $data, false);

        CertidigitalAssesmentsModel::where('uid', $assesment['ocbid'])->update(['certidigital_achievement_uid' => $achievement['ocbid']]);
    }

    private function createAchievement($name, $achievementChilds = null, $learningResults = null, $assesments = null, $activities = null)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $name,
                        "language" => "es"

                    ]
                ]
            ],
        ];

        if ($achievementChilds) {
            $data['relSubAchievements'] = [
                "oid" => array_column($achievementChilds, "oid")
            ];
        }

        if ($learningResults) {
            $data['relLearningOutcomes'] = [
                "oid" => array_column($learningResults, "oid")
            ];
        }

        if ($activities) {
            $data['relInfluencedBy'] = [
                "oid" => array_column($activities, "oid")
            ];
        }

        $response = $this->sendRequest($endpoint, $data);
        $ahievementData = $response->json();

        CertidigitalAchievementsModel::create([
            'uid' => $ahievementData['ocbid'],
            'id' => $ahievementData['oid'],
            'title' => $name
        ]);

        if ($achievementChilds) {
            CertidigitalAchievementsModel::whereIn('uid', array_column($achievementChilds, "ocbid"))->update(['certidigital_achievement_uid' => $ahievementData['ocbid']]);
        }

        if ($learningResults) {
            CertidigitalLearningOutcomesModel::whereIn('uid', array_column($learningResults, "ocbid"))->update(['certidigital_achievement_uid' => $ahievementData['ocbid']]);
        }

        if ($activities) {
            CertidigitalActivitiesModel::whereIn('uid', array_column($activities, "ocbid"))->update(['certidigital_achievement_uid' => $ahievementData['ocbid']]);
        }

        if ($assesments) {
            foreach ($assesments as $assesment) {
                $this->vinculateAssesmentToAchievement($assesment, $ahievementData);
            }
        }

        return $response->json();
    }

    private function createActivity($activityName)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/activities?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $activityName,
                        "language" => "es"

                    ]
                ]
            ],
        ];

        $response = $this->sendRequest($endpoint, $data);

        CertidigitalActivitiesModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $activityName
        ]);

        return $response->json();
    }

    private function createAssesment($name, $courseUid = null, $courseBlockUid = null, $learningResultUid = null)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/assessments?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $name,
                        "language" => "es"
                    ]
                ]
            ]
        ];

        $response = $this->sendRequest($endpoint, $data);

        CertidigitalAssesmentsModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $name,
            'course_block_uid' => $courseBlockUid,
            'learning_result_uid' => $learningResultUid,
            'course_uid' => $courseUid
        ]);

        return $response->json();
    }

    private function deleteActivities($activities)
    {
        $generalOptions = app('general_options');

        foreach ($activities as $activity) {
            $activityOid = $activity['id'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/activities/$activityOid";

            $this->sendRequest($endpoint, [], false, "delete");

            $activity->delete();
        }
    }

    private function deleteAssesments($assesments)
    {

        $generalOptions = app('general_options');

        foreach ($assesments as $assesment) {
            $assesmentOid = $assesment['id'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/assessments/$assesmentOid";

            //TODO Eliminar las evaluaciones

            $assesment->delete();
        }
    }

    private function deleteLearningOutcomes($learningOutcomes)
    {
        $generalOptions = app('general_options');

        foreach ($learningOutcomes as $learningOutcome) {
            $learningOutcomeOid = $learningOutcome['oid'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/learning-outcomes/$learningOutcomeOid";

            $this->sendRequest($endpoint, [], false, "delete");

            $learningOutcome->delete();
        }
    }

    private function deleteAchievements($achievements)
    {
        $generalOptions = app('general_options');

        foreach ($achievements as $achievement) {

            $activievementOid = $achievement['oid'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements/$activievementOid";

            $this->sendRequest($endpoint, [], false, "delete");
            $achievement->delete();
        }
    }

    private function createLearningOutcome($learningResultName, $learningResultOriginCode)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/learning-outcomes?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $learningResultName,
                        "language" => "es"
                    ]
                ]
            ],
        ];

        if ($learningResultOriginCode) {
            $data['relatedESCOSkill'] = [
                [
                    "uri" => $learningResultOriginCode,
                    "targetName" => [
                        "contents" => [
                            [
                                "content" => $learningResultName,
                                "language" => "es",
                                "format" => "text/plain"
                            ]
                        ]
                    ],
                ]
            ];
        }

        $response = $this->sendRequest($endpoint, $data);

        CertidigitalLearningOutcomesModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $learningResultName
        ]);

        return $response->json();
    }

    private function sendRequest($endpoint, $data, $addCommonFields = true, $method = 'POST')
    {
        $token = $this->getValidToken();

        if ($addCommonFields) {
            // Añadir al data los datos por defecto
            $data['defaultLanguage'] = 'es';
            $data['additionalInfo'] = [
                "languages" => [
                    "es"
                ]
            ];

            $data["relAwardingBody"] = [
                "oid" => [app('general_options')['certidigital_organization_oid']]
            ];
        }

        $response = $this->request($endpoint, $data, $token, $method);

        if (!in_array($response->status(), [200, 201, 204, 404])) {
            throw new Exception('Error in the request to Certidigital ' . $response->status());
        }

        return $response;
    }

    // Se recorre todos los bloques del curso y se saca de forma unitaria los resultados de aprendizaje
    private function getAllLearningResultsCourse($course)
    {
        $learningResults = [];

        foreach ($course->blocks as $block) {
            foreach ($block->learningResults as $learningResult) {
                $learningResults[$learningResult->uid] = $learningResult->toArray();
            }
        }

        return $learningResults;
    }

    private function refreshToken()
    {
        $generalOptions = app('general_options');

        $params = [
            'grant_type' => 'password',
            'client_id' => $generalOptions['certidigital_client_id'],
            'client_secret' => $generalOptions['certidigital_client_secret'],
            'username' => $generalOptions['certidigital_username'],
            'password' => $generalOptions['certidigital_password'],
        ];

        $certidigitalUrlToken = $generalOptions['certidigital_url_token'];
        $response = $this->request($certidigitalUrlToken, $params);

        if ($response->status() != 200) {
            throw new \Exception('Error refreshing Certidigital token');
        }

        session()->put('certidigital_token', $response->json()['access_token']);
        session()->put('certidigital_token_expires', now()->addSeconds($response->json()['expires_in']));

        return $response->json()['access_token'];
    }

    private function getValidToken()
    {
        $token = session('certidigital_token');
        $tokenExpires = session('certidigital_token_expires');

        if (!$token || !$tokenExpires || now()->greaterThan($tokenExpires)) {
            $token = $this->refreshToken();
        }

        return $token;
    }

    private function request($url, $data, $token = null, $method = "POST")
    {
        if ($token) {
            $httpRequest = Http::withToken($token);
        } else {
            $httpRequest = Http::asForm();
        }

        $httpRequest->withoutVerifying();

        return $httpRequest->$method($url, $data);
    }
}
