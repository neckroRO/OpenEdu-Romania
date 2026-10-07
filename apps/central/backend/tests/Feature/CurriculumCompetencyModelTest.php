<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\CompetencyConcept;
use App\Models\Concept;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumCompetencyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_competencies_can_form_a_general_specific_hierarchy(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $general = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '1',
            'type' => 'general',
            'title' => 'Receptarea mesajelor orale',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $specific = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => $general->id,
            'code' => '1.1',
            'type' => 'specific',
            'title' => 'Identificarea semnificației unui mesaj oral',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $this->assertTrue(
            $specific->parent->is($general)
        );

        $this->assertTrue(
            $general->children()
                ->whereKey($specific->id)
                ->exists()
        );
    }

    public function test_curriculum_subject_has_competencies(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $competency = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '1',
            'type' => 'general',
            'title' => 'Competență generală',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $this->assertTrue(
            $competency->curriculumSubject->is($curriculumSubject)
        );

        $this->assertTrue(
            $curriculumSubject->competencies()
                ->whereKey($competency->id)
                ->exists()
        );
    }

    public function test_competency_can_be_linked_to_concepts(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $competency = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '1.1',
            'type' => 'specific',
            'title' => 'Utilizarea conceptului',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $concept = Concept::create([
            'code' => 'test-concept',
            'title' => 'Concept de test',
            'description' => 'Concept folosit pentru testarea competențelor.',
            'status' => 'active',
        ]);

        $link = CompetencyConcept::create([
            'competency_id' => $competency->id,
            'concept_id' => $concept->id,
            'display_order' => 2,
            'is_core' => false,
        ]);

        $this->assertTrue(
            $competency->concepts()
                ->whereKey($concept->id)
                ->exists()
        );

        $this->assertTrue(
            $concept->competencies()
                ->whereKey($competency->id)
                ->exists()
        );

        $this->assertSame(
            2,
            $competency->concepts()
                ->firstOrFail()
                ->pivot
                ->display_order
        );

        $this->assertFalse(
            $link->fresh()->is_core
        );
    }

    public function test_deleting_parent_competency_cascades_to_children(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $general = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '1',
            'type' => 'general',
            'title' => 'Competență generală',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $specific = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => $general->id,
            'code' => '1.1',
            'type' => 'specific',
            'title' => 'Competență specifică',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $general->delete();

        $this->assertDatabaseMissing('competencies', [
            'id' => $general->id,
        ]);

        $this->assertDatabaseMissing('competencies', [
            'id' => $specific->id,
        ]);
    }

    public function test_deleting_curriculum_subject_cascades_competencies_and_links(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $competency = Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => '1',
            'type' => 'general',
            'title' => 'Competență generală',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $concept = Concept::create([
            'code' => 'cascade-concept',
            'title' => 'Concept pentru cascade',
            'status' => 'active',
        ]);

        $link = CompetencyConcept::create([
            'competency_id' => $competency->id,
            'concept_id' => $concept->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        $curriculumSubject->delete();

        $this->assertDatabaseMissing('competencies', [
            'id' => $competency->id,
        ]);

        $this->assertDatabaseMissing('competency_concepts', [
            'id' => $link->id,
        ]);

        $this->assertDatabaseHas('concepts', [
            'id' => $concept->id,
        ]);
    }

    private function createCurriculumSubject(): CurriculumSubject
    {
        $curriculum = Curriculum::create([
            'code' => 'RO-TEST',
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'grade-test',
            'name' => 'Clasă de test',
            'ordinal' => 1,
            'education_stage' => 'primar',
        ]);

        $subject = Subject::create([
            'code' => 'test-subject',
            'name' => 'Disciplină de test',
            'status' => 'active',
        ]);

        return CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
