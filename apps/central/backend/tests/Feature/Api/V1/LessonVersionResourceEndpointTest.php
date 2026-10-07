<?php

namespace Tests\Feature\Api\V1;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\LessonVersionResource;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LessonVersionResourceEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_replace_resources_on_own_draft(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();
        $version = $this->makeVersion(
            $lesson,
            $teacher,
            'draft'
        );

        $explanation = $this->makeResource();
        $exercise = $this->makeResource();

        Sanctum::actingAs($teacher);

        $this->putJson(
            "/api/v1/lesson-versions/{$version->id}/resources",
            [
                'resources' => [
                    [
                        'resource_id' => $explanation->id,
                        'role' => 'explanation',
                        'display_order' => 20,
                        'is_required' => true,
                    ],
                    [
                        'resource_id' => $exercise->id,
                        'role' => 'exercise',
                        'display_order' => 10,
                        'is_required' => false,
                    ],
                ],
            ]
        )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.resource.id',
                $exercise->id
            )
            ->assertJsonPath(
                'data.0.role',
                'exercise'
            )
            ->assertJsonPath(
                'data.0.is_required',
                false
            )
            ->assertJsonPath(
                'data.1.resource.id',
                $explanation->id
            );

        $this->assertDatabaseCount(
            'lesson_version_resources',
            2
        );
    }

    public function test_teacher_can_clear_all_resources_from_own_draft(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $teacher,
            'draft'
        );

        $resource = $this->makeResource();

        LessonVersionResource::create([
            'lesson_version_id' => $version->id,
            'resource_id' => $resource->id,
            'role' => 'explanation',
            'display_order' => 1,
            'is_required' => true,
        ]);

        Sanctum::actingAs($teacher);

        $this->putJson(
            "/api/v1/lesson-versions/{$version->id}/resources",
            [
                'resources' => [],
            ]
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount(
            'lesson_version_resources',
            0
        );
    }

    public function test_teacher_cannot_change_another_teachers_resources(): void
    {
        $owner = User::factory()->create([
            'role' => 'teacher',
        ]);

        $otherTeacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $owner,
            'draft'
        );

        $resource = $this->makeResource();

        Sanctum::actingAs($otherTeacher);

        $this->putJson(
            "/api/v1/lesson-versions/{$version->id}/resources",
            [
                'resources' => [
                    [
                        'resource_id' => $resource->id,
                        'role' => 'example',
                        'display_order' => 1,
                        'is_required' => true,
                    ],
                ],
            ]
        )->assertForbidden();

        $this->assertDatabaseCount(
            'lesson_version_resources',
            0
        );
    }

    public function test_resources_cannot_be_changed_after_draft(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $teacher,
            'submitted'
        );

        $resource = $this->makeResource();

        Sanctum::actingAs($teacher);

        $this->putJson(
            "/api/v1/lesson-versions/{$version->id}/resources",
            [
                'resources' => [
                    [
                        'resource_id' => $resource->id,
                        'role' => 'example',
                        'display_order' => 1,
                        'is_required' => true,
                    ],
                ],
            ]
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'LESSON_VERSION_NOT_EDITABLE'
            );

        $this->assertDatabaseCount(
            'lesson_version_resources',
            0
        );
    }

    public function test_inactive_resource_is_rejected_without_losing_existing_links(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $lesson = $this->makeLesson();

        $version = $this->makeVersion(
            $lesson,
            $teacher,
            'draft'
        );

        $existing = $this->makeResource();

        $inactive = $this->makeResource(
            'inactive'
        );

        LessonVersionResource::create([
            'lesson_version_id' => $version->id,
            'resource_id' => $existing->id,
            'role' => 'explanation',
            'display_order' => 1,
            'is_required' => true,
        ]);

        Sanctum::actingAs($teacher);

        $this->putJson(
            "/api/v1/lesson-versions/{$version->id}/resources",
            [
                'resources' => [
                    [
                        'resource_id' => $inactive->id,
                        'role' => 'supplementary',
                        'display_order' => 1,
                        'is_required' => false,
                    ],
                ],
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'error.code',
                'LESSON_RESOURCE_NOT_ACTIVE'
            );

        $this->assertDatabaseHas(
            'lesson_version_resources',
            [
                'lesson_version_id' => $version->id,
                'resource_id' => $existing->id,
            ]
        );

        $this->assertDatabaseMissing(
            'lesson_version_resources',
            [
                'lesson_version_id' => $version->id,
                'resource_id' => $inactive->id,
            ]
        );
    }

    private function makeLesson(): Lesson
    {
        $suffix = uniqid();

        $curriculum = Curriculum::create([
            'code' => 'RO-'.$suffix,
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $curriculumVersion = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-'.$suffix,
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'grade-'.$suffix,
            'name' => 'Clasă test',
            'ordinal' => 7,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'subject-'.$suffix,
            'name' => 'Disciplină '.$suffix,
            'status' => 'active',
        ]);

        $curriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' =>
                $curriculumVersion->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);

        return Lesson::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'code' => 'lesson-'.$suffix,
            'title' => 'Lecție test',
            'display_order' => 1,
            'status' => 'active',
        ]);
    }

    private function makeVersion(
        Lesson $lesson,
        User $creator,
        string $status
    ): LessonVersion {
        return LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Versiune test',
            'language_code' => 'ro',
            'status' => $status,
            'created_by' => $creator->id,
            'submitted_at' =>
                $status === 'submitted'
                    ? now()
                    : null,
        ]);
    }

    private function makeResource(
        string $status = 'active'
    ): Resource {
        return Resource::create([
            'code' => 'resource-'.uniqid(),
            'type' => 'lesson-material',
            'status' => $status,
        ]);
    }
}
