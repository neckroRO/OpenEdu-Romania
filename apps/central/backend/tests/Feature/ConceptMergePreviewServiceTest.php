<?php

namespace Tests\Feature;

use App\Data\Curriculum\ConceptMergePreview;
use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumAlias;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Subject;
use App\Services\Curriculum\ConceptMergePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConceptMergePreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_modify_concepts(): void
    {
        [$source, $target] = $this->concepts();

        $preview = app(
            ConceptMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $this->assertInstanceOf(
            ConceptMergePreview::class,
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

    public function test_preview_detects_same_concept_as_blocking(): void
    {
        [$source] = $this->concepts();

        $preview = app(
            ConceptMergePreviewService::class
        )->preview(
            $source,
            $source
        );

        $this->assertTrue($preview->blocked);

        $this->assertContains(
            'same_concept',
            $preview->blockingReasons
        );
    }

    public function test_preview_detects_merged_source_and_target(): void
    {
        [$source, $target] = $this->concepts();

        $source->update([
            'status' => 'merged',
        ]);

        $target->update([
            'status' => 'merged',
        ]);

        $preview = app(
            ConceptMergePreviewService::class
        )->preview(
            $source->refresh(),
            $target->refresh()
        );

        $this->assertTrue($preview->blocked);

        $this->assertContains(
            'source_already_merged',
            $preview->blockingReasons
        );

        $this->assertContains(
            'target_already_merged',
            $preview->blockingReasons
        );
    }

    public function test_preview_counts_placement_actions(): void
    {
        [$source, $target] = $this->concepts();

        $domainA = $this->domain('Algebră', 10);
        $domainB = $this->domain('Ecuații', 20);

        ConceptPlacement::create([
            'concept_id' => $source->id,
            'domain_id' => $domainA->id,
            'display_order' => 10,
            'is_core' => true,
        ]);

        ConceptPlacement::create([
            'concept_id' => $source->id,
            'domain_id' => $domainB->id,
            'display_order' => 20,
            'is_core' => true,
        ]);

        ConceptPlacement::create([
            'concept_id' => $target->id,
            'domain_id' => $domainA->id,
            'display_order' => 30,
            'is_core' => false,
        ]);

        $preview = app(
            ConceptMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $this->assertSame(
            1,
            $preview->placementsToMove
        );

        $this->assertSame(
            1,
            $preview->placementsToDeduplicate
        );

        $this->assertFalse($preview->blocked);
    }

    public function test_preview_counts_alias_actions(): void
    {
        [$source, $target] = $this->concepts();

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
            'entity_id' => $source->id,
            'alias' => 'Ecuație simplă',
            'normalized_alias' => 'ecuatie simpla',
            'source' => 'community',
            'status' => 'approved',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'concept',
            'entity_id' => $target->id,
            'alias' => 'Ecuație grad I',
            'normalized_alias' => 'ecuatie grad i',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $preview = app(
            ConceptMergePreviewService::class
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

        $this->assertTrue(
            $preview->sourceTitleAliasWillBeCreated
        );
    }

    public function test_preview_does_not_create_source_title_alias_when_equivalent(): void
    {
        $source = Concept::create([
            'code' => 'linear-equation-community',
            'title' => 'Ecuație liniară',
            'status' => 'active',
        ]);

        $target = Concept::create([
            'code' => 'linear-equation',
            'title' => 'ECUATIE LINIARA',
            'status' => 'active',
        ]);

        $preview = app(
            ConceptMergePreviewService::class
        )->preview(
            $source,
            $target
        );

        $this->assertFalse(
            $preview->sourceTitleAliasWillBeCreated
        );
    }

    public function test_preview_can_be_serialized(): void
    {
        [$source, $target] = $this->concepts();

        $array = app(
            ConceptMergePreviewService::class
        )->preview(
            $source,
            $target
        )->toArray();

        $this->assertSame(
            $source->id,
            $array['source_concept_id']
        );

        $this->assertSame(
            $target->id,
            $array['target_concept_id']
        );

        $this->assertArrayHasKey(
            'placements_to_move',
            $array
        );

        $this->assertArrayHasKey(
            'competencies_to_move',
            $array
        );

        $this->assertArrayHasKey(
            'lessons_to_move',
            $array
        );

        $this->assertArrayHasKey(
            'resources_to_move',
            $array
        );

        $this->assertArrayHasKey(
            'aliases_to_move',
            $array
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

    private function concepts(): array
    {
        return [
            Concept::create([
                'code' => 'linear-equation-community',
                'title' => 'Ecuație de gradul I',
                'status' => 'active',
            ]),

            Concept::create([
                'code' => 'linear-equation',
                'title' => 'Ecuație liniară',
                'status' => 'active',
            ]),
        ];
    }

    private function domain(
        string $title,
        int $displayOrder
    ): Domain {
        return Domain::create([
            'curriculum_subject_id' =>
                $this->curriculumSubject()->id,
            'title' => $title,
            'display_order' => $displayOrder,
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
