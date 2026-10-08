<?php

namespace Tests\Feature;

use App\Models\CurriculumAlias;
use App\Models\CurriculumEntityMerge;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use App\Services\Curriculum\SubjectMergeService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_cannot_be_merged_into_itself(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $this->expectException(DomainException::class);

        app(SubjectMergeService::class)->merge(
            $subject,
            $subject,
            $user
        );
    }

    public function test_subject_merge_is_recorded_and_source_is_marked_merged(): void
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

        $user = User::factory()->create();

        $merge = app(SubjectMergeService::class)->merge(
            $source,
            $target,
            $user,
            'Duplicate confirmed.'
        );

        $this->assertInstanceOf(
            CurriculumEntityMerge::class,
            $merge
        );

        $this->assertSame(
            $source->id,
            $merge->source_entity_id
        );

        $this->assertSame(
            $target->id,
            $merge->target_entity_id
        );

        $this->assertSame(
            'merged',
            $source->refresh()->status
        );
    }

    public function test_source_name_becomes_approved_system_alias(): void
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

        $user = User::factory()->create();

        app(SubjectMergeService::class)->merge(
            $source,
            $target,
            $user
        );

        $this->assertDatabaseHas('curriculum_aliases', [
            'entity_type' => 'subject',
            'entity_id' => $target->id,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'system',
            'status' => 'approved',
        ]);
    }

    public function test_existing_source_aliases_are_moved_to_target(): void
    {
        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Matematică alternativ',
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
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $user = User::factory()->create();

        app(SubjectMergeService::class)->merge(
            $source,
            $target,
            $user
        );

        $this->assertDatabaseHas('curriculum_aliases', [
            'entity_type' => 'subject',
            'entity_id' => $target->id,
            'normalized_alias' => 'mate',
        ]);

        $this->assertDatabaseMissing('curriculum_aliases', [
            'entity_type' => 'subject',
            'entity_id' => $source->id,
        ]);
    }

    public function test_reputations_for_same_user_are_consolidated(): void
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

        $user = User::factory()->create();
        $merger = User::factory()->create();

        UserSubjectReputation::create([
            'user_id' => $user->id,
            'subject_id' => $source->id,
            'score' => 10,
            'contribution_count' => 2,
            'review_count' => 1,
        ]);

        UserSubjectReputation::create([
            'user_id' => $user->id,
            'subject_id' => $target->id,
            'score' => 20,
            'contribution_count' => 3,
            'review_count' => 4,
        ]);

        app(SubjectMergeService::class)->merge(
            $source,
            $target,
            $merger
        );

        $reputation = UserSubjectReputation::query()
            ->where('user_id', $user->id)
            ->where('subject_id', $target->id)
            ->firstOrFail();

        $this->assertSame(30, $reputation->score);
        $this->assertSame(5, $reputation->contribution_count);
        $this->assertSame(5, $reputation->review_count);

        $this->assertDatabaseMissing(
            'user_subject_reputations',
            [
                'user_id' => $user->id,
                'subject_id' => $source->id,
            ]
        );
    }

    public function test_already_merged_subject_cannot_be_merged_again(): void
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

        $otherTarget = Subject::create([
            'code' => 'other-math',
            'name' => 'Matematică nouă',
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $service = app(SubjectMergeService::class);

        $service->merge(
            $source,
            $target,
            $user
        );

        $this->expectException(DomainException::class);

        $service->merge(
            $source->refresh(),
            $otherTarget,
            $user
        );
    }

    public function test_curriculum_subject_collision_aborts_entire_merge(): void
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

        $curriculum = \App\Models\Curriculum::create([
            'code' => 'TEST-CURRICULUM',
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = \App\Models\CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-1',
            'valid_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $level = \App\Models\EducationLevel::create([
            'code' => 'test-grade',
            'name' => 'Clasa test',
            'ordinal' => 1,
            'education_stage' => 'test',
        ]);

        $variant = \App\Models\CurriculumFrameworkVariant::create([
            'curriculum_version_id' => $version->id,
            'code' => 'standard-test',
            'name' => 'Variantă test',
            'status' => 'active',
        ]);

        \App\Models\CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'curriculum_framework_variant_id' => $variant->id,
            'education_level_id' => $level->id,
            'subject_id' => $source->id,
            'component' => 'TC',
            'display_order' => 1,
            'status' => 'active',
        ]);

        \App\Models\CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'curriculum_framework_variant_id' => $variant->id,
            'education_level_id' => $level->id,
            'subject_id' => $target->id,
            'component' => 'TC',
            'display_order' => 2,
            'status' => 'active',
        ]);

        $merger = User::factory()->create();

        try {
            app(SubjectMergeService::class)->merge(
                $source,
                $target,
                $merger,
                'Duplicate test.'
            );

            $this->fail(
                'Expected curriculum subject collision was not detected.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'The merge would create a duplicate curriculum subject.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            'active',
            $source->refresh()->status
        );

        $this->assertDatabaseMissing(
            'curriculum_entity_merges',
            [
                'entity_type' => 'subject',
                'source_entity_id' => $source->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_subjects',
            [
                'subject_id' => $source->id,
                'curriculum_version_id' => $version->id,
                'education_level_id' => $level->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_subjects',
            [
                'subject_id' => $target->id,
                'curriculum_version_id' => $version->id,
                'education_level_id' => $level->id,
            ]
        );
    }


    public function test_curriculum_subject_is_repointed_when_no_collision_exists(): void
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

        $curriculum = \App\Models\Curriculum::create([
            'code' => 'TEST-CURRICULUM',
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = \App\Models\CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-1',
            'valid_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $level = \App\Models\EducationLevel::create([
            'code' => 'test-grade',
            'name' => 'Clasa test',
            'ordinal' => 1,
            'education_stage' => 'test',
        ]);

        $variant = \App\Models\CurriculumFrameworkVariant::create([
            'curriculum_version_id' => $version->id,
            'code' => 'standard-test',
            'name' => 'Variantă test',
            'status' => 'active',
        ]);

        $link = \App\Models\CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'curriculum_framework_variant_id' => $variant->id,
            'education_level_id' => $level->id,
            'subject_id' => $source->id,
            'component' => 'TC',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $merger = User::factory()->create();

        app(SubjectMergeService::class)->merge(
            $source,
            $target,
            $merger
        );

        $this->assertSame(
            $target->id,
            $link->refresh()->subject_id
        );

        $this->assertDatabaseMissing(
            'curriculum_subjects',
            [
                'id' => $link->id,
                'subject_id' => $source->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_subjects',
            [
                'id' => $link->id,
                'subject_id' => $target->id,
            ]
        );
    }

}
