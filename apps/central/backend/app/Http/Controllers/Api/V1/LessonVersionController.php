<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LessonVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonVersionRequest;
use App\Http\Requests\UpdateLessonVersionRequest;
use App\Http\Resources\EditorialLessonVersionResource;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LessonVersionController extends Controller
{
    public function store(
        StoreLessonVersionRequest $request,
        Lesson $lesson
    ): JsonResponse {
        Gate::authorize(
            'create',
            LessonVersion::class
        );

        if ($lesson->status !== 'active') {
            return ApiResponse::error(
                code: 'LESSON_NOT_EDITABLE',
                message: 'Lecția nu este activă.',
                status: 409,
            );
        }

        $validated = $request->validated();
        $user = $request->user();

        $result = DB::transaction(
            function () use (
                $lesson,
                $validated,
                $user
            ) {
                $lockedLesson = Lesson::query()
                    ->whereKey($lesson->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $inProgress = $lockedLesson->versions()
                    ->whereIn(
                        'status',
                        [
                            LessonVersionStatus::Draft->value,
                            LessonVersionStatus::Submitted->value,
                            LessonVersionStatus::Rejected->value,
                            LessonVersionStatus::Approved->value,
                        ]
                    )
                    ->exists();

                if ($inProgress) {
                    return null;
                }

                $nextVersion = (
                    (int) $lockedLesson->versions()
                        ->max('version_number')
                ) + 1;

                return $lockedLesson->versions()->create([
                    'version_number' => $nextVersion,
                    'summary' => $validated['summary'] ?? null,
                    'learning_objectives' =>
                        $validated['learning_objectives'] ?? null,
                    'content' => $validated['content'] ?? null,
                    'estimated_duration_minutes' =>
                        $validated['estimated_duration_minutes']
                            ?? null,
                    'language_code' =>
                        $validated['language_code'] ?? 'ro',
                    'status' => LessonVersionStatus::Draft,
                    'created_by' => $user->id,
                ]);
            }
        );

        if ($result === null) {
            return ApiResponse::error(
                code: 'LESSON_VERSION_IN_PROGRESS',
                message:
                    'Există deja o versiune editorială în lucru pentru această lecție.',
                status: 409,
            );
        }

        return ApiResponse::success(
            (new EditorialLessonVersionResource(
                $result
            ))->resolve(),
            201
        );
    }

    public function update(
        UpdateLessonVersionRequest $request,
        LessonVersion $lessonVersion
    ): JsonResponse {
        Gate::authorize(
            'update',
            $lessonVersion
        );

        if (
            $lessonVersion->status
            !== LessonVersionStatus::Draft
        ) {
            return ApiResponse::error(
                code: 'LESSON_VERSION_NOT_EDITABLE',
                message: 'Doar o versiune draft poate fi editată.',
                status: 409,
            );
        }

        $validated = $request->validated();

        $fields = array_intersect_key(
            $validated,
            array_flip([
                'summary',
                'learning_objectives',
                'content',
                'estimated_duration_minutes',
                'language_code',
            ])
        );

        if ($fields !== []) {
            $lessonVersion->update($fields);
        }

        return ApiResponse::success(
            (new EditorialLessonVersionResource(
                $lessonVersion->refresh()
            ))->resolve()
        );
    }
}
