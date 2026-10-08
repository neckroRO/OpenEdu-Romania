<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\CompetencyConcept;
use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumAlias;
use App\Models\CurriculumEntityMerge;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonConcept;
use App\Models\Resource;
use App\Models\ResourceConcept;
use App\Models\Subject;
use App\Models\User;
use App\Services\Curriculum\ConceptMergeService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConceptMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_moves_and_deduplicates_all_concept_relations(): void
    {
        $merger = User::factory()->create();

        $curriculumSubject = $this->curriculumSubject();

        $source = $this->concept(
            'linear-equation-community',
            'Ecuație de gradul I'
        );

        $target = $this->concept(
            'linear-equation',
            'Ecuație liniară'
        );

        $domainShared = $this->domain(
            $curriculumSubject,
            'Algebră'
        );

        $domainMove = $this->domain(
            $curriculumSubject,
            'Ecuații'
        );

        ConceptPlacement::create([
            'concept_id' => $source->id,
            'domain_id' => $domainShared->id,
            'display_order' => 10,
            'is_core' => true,
        ]);

        ConceptPlacement::create([
            'concept_id' => $target->id,
            'domain_id' => $domainShared->id,
            'display_order' => 30,
            'is_core' => false,
        ]);

        ConceptPlacement::create([
            'concept_id' => $source->id,
            'domain_id' => $domainMove->id,
            'display_order' => 20,
            'is_core' => false,
        ]);

        $competencyShared = $this->competency(
            $curriculumSubject,
            'COMP-SHARED'
        );

        $competencyMove = $this->competency(
            $curriculumSubject,
            'COMP-MOVE'
        );

        CompetencyConcept::create([
            'competency_id' => $competencyShared->id,
            'concept_id' => $source->id,
            'display_order' => 5,
            'is_core' => true,
        ]);

        CompetencyConcept::create([
            'competency_id' => $competencyShared->id,
            'concept_id' => $target->id,
            'display_order' => 25,
            'is_core' => false,
        ]);

        CompetencyConcept::create([
            'competency_id' => $competencyMove->id,
            'concept_id' => $source->id,
            'display_order' => 15,
            'is_core' => false,
        ]);

        $lessonShared = $this->lesson(
            $curriculumSubject,
            'lesson-shared'
        );

        $lessonMove = $this->lesson(
            $curriculumSubject,
            'lesson-move'
        );

        LessonConcept::create([
            'lesson_id' => $lessonShared->id,
            'concept_id' => $source->id,
            'display_order' => 4,
            'is_core' => true,
        ]);

        LessonConcept::create([
            'lesson_id' => $lessonShared->id,
            'concept_id' => $target->id,
            'display_order' => 24,
            'is_core' => false,
        ]);

        LessonConcept::create([
            'lesson_id' => $lessonMove->id,
            'concept_id' => $source->id,
            'display_order' => 14,
            'is_core' => false,
        ]);

        $resourceShared = $this->resource(
            'resource-shared'
        );

        $resourceMove = $this->resource(
            'resource-move'
        );

        ResourceConcept::create([
            'resource_id' => $resourceShared->id,
            'concept_id' => $source->id,
            'is_primary' => true,
            'display_order' => 3,
        ]);

        ResourceConcept::create([
            'resource_id' => $resourceShared->id,
            'concept_id' => $target->id,
            'is_primary' => false,
            'display_order' => 23,
        ]);

        ResourceConcept::create([
            'resource_id' => $resourceMove->id,
            'concept_id' => $source->id,
            'is_primary' => false,
            'display_order' => 13,
        ]);

        CurriculumAlias::create([
            'entity_type' => 'concept',
            'entity_id' => $source->id,
            'alias' => 'Ecuație simplă',
            'normalized_alias' => 'ecuatie simpla',
            'source' => 'community',
            'status' => 'approved',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'concept',
            'entity_id' => $source->id,
            'alias' => 'Ecuație grad I',
            'normalized_alias' => 'ecuatie grad i',
            'source' => 'community',
            'status' => 'approved',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'concept',
            'entity_id' => $target->id,
            'alias' => 'Ecuație simplă',
            'normalized_alias' => 'ecuatie simpla',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $merge = app(
            ConceptMergeService::class
        )->merge(
            $source,
            $target,
            $merger,
            'Concept duplicat.'
        );

        $this->assertSame(
            'concept',
            $merge->entity_type
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

        $this->assertSame(
            'active',
            $target->refresh()->status
        );

        $this->assertDatabaseMissing(
            'concept_placements',
            [
                'concept_id' => $source->id,
            ]
        );

        $this->assertDatabaseHas(
            'concept_placements',
            [
                'concept_id' => $target->id,
                'domain_id' => $domainShared->id,
                'display_order' => 10,
                'is_core' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'concept_placements',
            [
                'concept_id' => $target->id,
                'domain_id' => $domainMove->id,
                'display_order' => 20,
                'is_core' => 0,
            ]
        );

        $this->assertDatabaseMissing(
            'competency_concepts',
            [
                'concept_id' => $source->id,
            ]
        );

        $this->assertDatabaseHas(
            'competency_concepts',
            [
                'competency_id' => $competencyShared->id,
                'concept_id' => $target->id,
                'display_order' => 5,
                'is_core' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'competency_concepts',
            [
                'competency_id' => $competencyMove->id,
                'concept_id' => $target->id,
                'display_order' => 15,
                'is_core' => 0,
            ]
        );

        $this->assertDatabaseMissing(
            'lesson_concepts',
            [
                'concept_id' => $source->id,
            ]
        );

        $this->assertDatabaseHas(
            'lesson_concepts',
            [
                'lesson_id' => $lessonShared->id,
                'concept_id' => $target->id,
                'display_order' => 4,
                'is_core' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'lesson_concepts',
            [
                'lesson_id' => $lessonMove->id,
                'concept_id' => $target->id,
                'display_order' => 14,
                'is_core' => 0,
            ]
        );

        $this->assertDatabaseMissing(
            'resource_concepts',
            [
                'concept_id' => $source->id,
            ]
        );

        $this->assertDatabaseHas(
            'resource_concepts',
            [
                'resource_id' => $resourceShared->id,
                'concept_id' => $target->id,
                'is_primary' => 1,
                'display_order' => 3,
            ]
        );

        $this->assertDatabaseHas(
            'resource_concepts',
            [
                'resource_id' => $resourceMove->id,
                'concept_id' => $target->id,
                'is_primary' => 0,
                'display_order' => 13,
            ]
        );

        $this->assertDatabaseMissing(
            'curriculum_aliases',
            [
                'entity_type' => 'concept',
                'entity_id' => $source->id,
            ]
        );

        $this->assertSame(
            1,
            CurriculumAlias::query()
                ->where('entity_type', 'concept')
                ->where('entity_id', $target->id)
                ->where(
                    'normalized_alias',
                    'ecuatie simpla'
                )
                ->count()
        );

        $this->assertDatabaseHas(
            'curriculum_aliases',
            [
                'entity_type' => 'concept',
                'entity_id' => $target->id,
                'normalized_alias' => 'ecuatie grad i',
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_entity_merges',
            [
                'entity_type' => 'concept',
                'source_entity_id' => $source->id,
                'target_entity_id' => $target->id,
                'merged_by' => $merger->id,
                'reason' => 'Concept duplicat.',
            ]
        );
    }

    public function test_concept_cannot_merge_into_itself(): void
    {
        $concept = $this->concept(
            'linear-equation',
            'Ecuație liniară'
        );

        $merger = User::factory()->create();

        $this->expectException(DomainException::class);

        app(ConceptMergeService::class)->merge(
            $concept,
            $concept,
            $merger
        );
    }

    public function test_already_merged_source_cannot_be_merged_again(): void
    {
        $source = $this->concept(
            'source-concept',
            'Concept sursă',
            'merged'
        );

        $target = $this->concept(
            'target-concept',
            'Concept țintă'
        );

        $this->expectException(DomainException::class);

        app(ConceptMergeService::class)->merge(
            $source,
            $target,
            User::factory()->create()
        );
    }

    public function test_merged_target_cannot_be_canonical_target(): void
    {
        $source = $this->concept(
            'source-concept',
            'Concept sursă'
        );

        $target = $this->concept(
            'target-concept',
            'Concept țintă',
            'merged'
        );

        $this->expectException(DomainException::class);

        app(ConceptMergeService::class)->merge(
            $source,
            $target,
            User::factory()->create()
        );
    }

    public function test_failed_merge_rolls_back_all_relation_changes(): void
    {
        $merger = User::factory()->create();

        $curriculumSubject = $this->curriculumSubject();

        $source = $this->concept(
            'rollback-source',
            'Concept rollback sursă'
        );

        $target = $this->concept(
            'rollback-target',
            'Concept rollback țintă'
        );

        $domain = $this->domain(
            $curriculumSubject,
            'Rollback'
        );

        ConceptPlacement::create([
            'concept_id' => $source->id,
            'domain_id' => $domain->id,
            'display_order' => 7,
            'is_core' => true,
        ]);

        CurriculumAlias::create([
            'entity_type' => 'concept',
            'entity_id' => $source->id,
            'alias' => 'Alias rollback',
            'normalized_alias' => 'alias rollback',
            'source' => 'community',
            'status' => 'approved',
        ]);

        /*
         * Force the final merge-record insert to fail.
         * The unique key is (entity_type, source_entity_id).
         */
        CurriculumEntityMerge::create([
            'entity_type' => 'concept',
            'source_entity_id' => $source->id,
            'target_entity_id' => 999999,
            'merged_by' => $merger->id,
            'merged_at' => now(),
            'reason' => 'Conflict artificial.',
        ]);

        try {
            app(ConceptMergeService::class)->merge(
                $source,
                $target,
                $merger,
                'Trebuie rollback.'
            );

            $this->fail(
                'Expected the merge record constraint to fail.'
            );
        } catch (QueryException) {
            // Expected.
        }

        $this->assertSame(
            'active',
            $source->refresh()->status
        );

        $this->assertDatabaseHas(
            'concept_placements',
            [
                'concept_id' => $source->id,
                'domain_id' => $domain->id,
                'display_order' => 7,
                'is_core' => 1,
            ]
        );

        $this->assertDatabaseMissing(
            'concept_placements',
            [
                'concept_id' => $target->id,
                'domain_id' => $domain->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_aliases',
            [
                'entity_type' => 'concept',
                'entity_id' => $source->id,
                'normalized_alias' => 'alias rollback',
            ]
        );

        $this->assertDatabaseMissing(
            'curriculum_aliases',
            [
                'entity_type' => 'concept',
                'entity_id' => $target->id,
                'normalized_alias' => 'alias rollback',
            ]
        );
    }

    private function concept(
        string $code,
        string $title,
        string $status = 'active'
    ): Concept {
        return Concept::create([
            'code' => $code,
            'title' => $title,
            'status' => $status,
        ]);
    }

    private function domain(
        CurriculumSubject $curriculumSubject,
        string $title
    ): Domain {
        return Domain::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'title' => $title,
            'display_order' => 1,
        ]);
    }

    private function competency(
        CurriculumSubject $curriculumSubject,
        string $code
    ): Competency {
        return Competency::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => $code,
            'type' => 'specific',
            'title' => $code,
            'description' => null,
            'display_order' => 1,
            'status' => 'active',
        ]);
    }

    private function lesson(
        CurriculumSubject $curriculumSubject,
        string $code
    ): Lesson {
        return Lesson::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
            'code' => $code,
            'title' => $code,
            'description' => null,
            'display_order' => 1,
            'status' => 'active',
        ]);
    }

    private function resource(string $code): Resource
    {
        return Resource::create([
            'code' => $code,
            'type' => 'explanation',
            'status' => 'active',
        ]);
    }

    private function curriculumSubject(): CurriculumSubject
    {
        $curriculum = Curriculum::create([
            'code' => 'TEST-'.uniqid(),
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-'.uniqid(),
            'valid_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'level-'.uniqid(),
            'name' => 'Nivel test',
            'ordinal' => 1,
            'education_stage' => 'test',
        ]);

        $subject = Subject::create([
            'code' => 'subject-'.uniqid(),
            'name' => 'Matematică test',
            'status' => 'active',
        ]);

        return CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'curriculum_framework_variant_id' => null,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'component' => 'TC',
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
