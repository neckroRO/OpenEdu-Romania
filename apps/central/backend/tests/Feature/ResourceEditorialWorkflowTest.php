<?php

namespace Tests\Feature;

use App\Enums\ResourceVersionStatus;
use App\Exceptions\InvalidEditorialTransitionException;
use App\Models\Resource;
use App\Models\ResourceVersion;
use App\Models\User;
use App\Services\ResourceEditorialWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceEditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_can_be_submitted(): void
    {
        $version = $this->makeDraftVersion();

        $result = app(ResourceEditorialWorkflow::class)->transition(
            $version,
            ResourceVersionStatus::Submitted
        );

        $this->assertSame(
            ResourceVersionStatus::Submitted,
            $result->status
        );

        $this->assertNotNull($result->submitted_at);
    }

    public function test_submitted_version_can_be_approved(): void
    {
        $reviewer = User::factory()->create();

        $version = $this->makeDraftVersion();

        $workflow = app(ResourceEditorialWorkflow::class);

        $version = $workflow->transition(
            $version,
            ResourceVersionStatus::Submitted
        );

        $result = $workflow->transition(
            $version,
            ResourceVersionStatus::Approved,
            $reviewer->id,
            'Conținut validat.'
        );

        $this->assertSame(
            ResourceVersionStatus::Approved,
            $result->status
        );

        $this->assertSame(
            $reviewer->id,
            $result->reviewed_by
        );

        $this->assertNotNull($result->reviewed_at);

        $this->assertSame(
            'Conținut validat.',
            $result->review_note
        );
    }

    public function test_rejected_version_keeps_review_feedback_when_returned_to_draft(): void
    {
        $reviewer = User::factory()->create();

        $version = $this->makeDraftVersion();

        $workflow = app(ResourceEditorialWorkflow::class);

        $version = $workflow->transition(
            $version,
            ResourceVersionStatus::Submitted
        );

        $version = $workflow->transition(
            $version,
            ResourceVersionStatus::Rejected,
            $reviewer->id,
            'Explicația trebuie simplificată.'
        );

        $result = $workflow->transition(
            $version,
            ResourceVersionStatus::Draft
        );

        $this->assertSame(
            ResourceVersionStatus::Draft,
            $result->status
        );

        $this->assertNull($result->submitted_at);

        $this->assertSame(
            $reviewer->id,
            $result->reviewed_by
        );

        $this->assertNotNull($result->reviewed_at);

        $this->assertSame(
            'Explicația trebuie simplificată.',
            $result->review_note
        );
    }

    public function test_approved_version_can_be_published(): void
    {
        $reviewer = User::factory()->create();

        $version = $this->makeDraftVersion();

        $workflow = app(ResourceEditorialWorkflow::class);

        $version = $workflow->transition(
            $version,
            ResourceVersionStatus::Submitted
        );

        $version = $workflow->transition(
            $version,
            ResourceVersionStatus::Approved,
            $reviewer->id
        );

        $result = $workflow->transition(
            $version,
            ResourceVersionStatus::Published
        );

        $this->assertSame(
            ResourceVersionStatus::Published,
            $result->status
        );

        $this->assertNotNull($result->published_at);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $version = $this->makeDraftVersion();

        $this->expectException(
            InvalidEditorialTransitionException::class
        );

        app(ResourceEditorialWorkflow::class)->transition(
            $version,
            ResourceVersionStatus::Published
        );
    }

    private function makeDraftVersion(): ResourceVersion
    {
        $resource = Resource::create([
            'code' => 'resource-' . uniqid(),
            'type' => 'lesson',
            'status' => 'active',
        ]);

        return ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Resursă de test',
            'summary' => 'Rezumat de test',
            'content' => 'Conținut de test',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => ResourceVersionStatus::Draft,
        ]);
    }
}
