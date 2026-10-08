<?php

namespace Tests\Feature;

use App\Data\Curriculum\SubjectMergePreview;
use App\Models\Curriculum;
use App\Models\CurriculumAlias;
use App\Models\CurriculumFrameworkVariant;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use App\Services\Curriculum\SubjectMergePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectMergePreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_modify_subjects(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $preview = app(
            SubjectMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $this->assertInstanceOf(
            SubjectMergePreview::class,
            $preview
        );

        $this->assertSame(
            'active',
            $source->refresh()->status
        );

        $this->assertSame(
            'active',
            $target->refresh()->status
        );

        $this->assertDatabaseCount(
            'curriculum_entity_merges',
            0
        );
    }

    public function test_preview_detects_same_subject_as_blocking(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $preview = app(
            SubjectMergePreviewService::class
        )->preview(
            $subject,
            $subject
        );

        $this->assertTrue($preview->blocked);

        $this->assertContains(
            'same_subject',
            $preview->blockingReasons
        );
    }

    public function test_preview_detects_curriculum_subject_collision(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $curriculum = Curriculum::create([
            'code' => 'TEST-CURRICULUM',
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-1',
            'valid_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'test-grade',
            'name' => 'Clasa test',
            'ordinal' => 1,
            'education_stage' => 'test',
        ]);

        $variant = CurriculumFrameworkVariant::create([
            'curriculum_version_id' => $version->id,
            'code' => 'standard-test',
            'name' => 'Variantă test',
            'status' => 'active',
        ]);

        foreach ([$source, $target] as $subject) {
            CurriculumSubject::create([
                'curriculum_version_id' => $version->id,
                'curriculum_framework_variant_id' =>
                    $variant->id,
                'education_level_id' => $level->id,
                'subject_id' => $subject->id,
                'component' => 'TC',
                'display_order' => 1,
                'status' => 'active',
            ]);
        }

        $preview = app(
            SubjectMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $this->assertTrue($preview->blocked);

        $this->assertSame(
            1,
            $preview->curriculumSubjectCollisions
        );

        $this->assertContains(
            'curriculum_subject_collision',
            $preview->blockingReasons
        );
    }

    public function test_preview_counts_alias_actions(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => $source->id,
            'alias' => 'Math',
            'normalized_alias' => 'math',
            'source' => 'community',
            'status' => 'approved',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => $source->id,
            'alias' => 'Mat',
            'normalized_alias' => 'mat',
            'source' => 'community',
            'status' => 'approved',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => $target->id,
            'alias' => 'Math',
            'normalized_alias' => 'math',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $preview = app(
            SubjectMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $this->assertSame(
            1,
            $preview->aliasesToMove
        );

        $this->assertSame(
            1,
            $preview->aliasesToDeduplicate
        );
    }

    public function test_preview_counts_reputation_actions(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $userWithBoth = User::factory()->create();
        $userWithSourceOnly = User::factory()->create();

        UserSubjectReputation::create([
            'user_id' => $userWithBoth->id,
            'subject_id' => $source->id,
            'score' => 10,
        ]);

        UserSubjectReputation::create([
            'user_id' => $userWithBoth->id,
            'subject_id' => $target->id,
            'score' => 20,
        ]);

        UserSubjectReputation::create([
            'user_id' => $userWithSourceOnly->id,
            'subject_id' => $source->id,
            'score' => 5,
        ]);

        $preview = app(
            SubjectMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $this->assertSame(
            1,
            $preview->reputationsToConsolidate
        );

        $this->assertSame(
            1,
            $preview->reputationsToMove
        );
    }

    public function test_preview_can_be_serialized(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $preview = app(
            SubjectMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $array = $preview->toArray();

        $this->assertSame(
            $source->id,
            $array['source_subject_id']
        );

        $this->assertSame(
            $target->id,
            $array['target_subject_id']
        );

        $this->assertArrayHasKey(
            'blocked',
            $array
        );

        $this->assertArrayHasKey(
            'blocking_reasons',
            $array
        );
    }
}
