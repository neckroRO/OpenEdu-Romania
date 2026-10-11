<?php

namespace App\Services\Curriculum;

use App\Data\Curriculum\ConceptMergePreview;
use App\Data\Curriculum\SubjectMergePreview;
use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\Concept;
use App\Models\CurriculumEntityMerge;
use App\Models\CurriculumProposal;
use App\Models\Subject;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CurriculumMergeModerationService
{
    public function __construct(
        private readonly SubjectMergePreviewService $subjectPreviewService,
        private readonly SubjectMergeService $subjectMergeService,
        private readonly ConceptMergePreviewService $conceptPreviewService,
        private readonly ConceptMergeService $conceptMergeService,
        private readonly CurriculumProposalWorkflow $proposalWorkflow,
        private readonly CurriculumAuditService $auditService,
    ) {
    }

    public function preview(
        CurriculumProposal $proposal
    ): SubjectMergePreview|ConceptMergePreview {
        $this->assertMergeCandidate($proposal);

        [$source, $target] = $this->resolveEntities(
            $proposal
        );

        return match ($proposal->entity_type) {
            'subject' =>
                $this->subjectPreviewService->preview(
                    $source,
                    $target
                ),

            'concept' =>
                $this->conceptPreviewService->preview(
                    $source,
                    $target
                ),

            default => throw new DomainException(
                'Unsupported merge proposal entity type.'
            ),
        };
    }

    public function confirm(
        CurriculumProposal $proposal,
        User $moderator,
        ?string $note = null
    ): CurriculumEntityMerge {
        $this->assertMergeCandidate($proposal);

        if (
            $proposal->status !== CurriculumProposalStatus::Pending
        ) {
            throw new DomainException(
                'Only pending merge proposals can be confirmed.'
            );
        }

        return DB::transaction(function () use (
            $proposal,
            $moderator,
            $note
        ): CurriculumEntityMerge {
            [$source, $target] = $this->resolveEntities(
                $proposal
            );

            $preview = $this->previewResolved(
                $proposal,
                $source,
                $target
            );

            if ($preview->blocked) {
                throw new DomainException(
                    'The proposed merge is blocked by preview validation.'
                );
            }

            $merge = $this->mergeResolved(
                $proposal,
                $source,
                $target,
                $moderator,
                $note ?? $proposal->reason
            );

            $this->proposalWorkflow->markMerged(
                $proposal->refresh(),
                $moderator,
                $note
            );

            $this->auditService->record(
                eventType: 'merge_completed',
                actor: $moderator,
                proposal: $proposal->refresh(),
                entityType: $proposal->entity_type,
                entityId: $target->id,
                metadata: [
                    'merge_id' => $merge->id,
                    'source_entity_id' => $source->id,
                    'target_entity_id' => $target->id,
                    'note' => $note,
                ]
            );

            return $merge;
        });
    }

    private function previewResolved(
        CurriculumProposal $proposal,
        Model $source,
        Model $target
    ): SubjectMergePreview|ConceptMergePreview {
        return match ($proposal->entity_type) {
            'subject' =>
                $this->subjectPreviewService->preview(
                    $source,
                    $target
                ),

            'concept' =>
                $this->conceptPreviewService->preview(
                    $source,
                    $target
                ),

            default => throw new DomainException(
                'Unsupported merge proposal entity type.'
            ),
        };
    }

    private function mergeResolved(
        CurriculumProposal $proposal,
        Model $source,
        Model $target,
        User $moderator,
        ?string $reason
    ): CurriculumEntityMerge {
        return match ($proposal->entity_type) {
            'subject' =>
                $this->subjectMergeService->merge(
                    $source,
                    $target,
                    $moderator,
                    $reason
                ),

            'concept' =>
                $this->conceptMergeService->merge(
                    $source,
                    $target,
                    $moderator,
                    $reason
                ),

            default => throw new DomainException(
                'Unsupported merge proposal entity type.'
            ),
        };
    }

    private function assertMergeCandidate(
        CurriculumProposal $proposal
    ): void {
        if (
            $proposal->proposal_type
            !== CurriculumProposalType::MergeCandidate
        ) {
            throw new DomainException(
                'The proposal is not a merge candidate.'
            );
        }

        if (
            ! in_array(
                $proposal->entity_type,
                ['subject', 'concept'],
                true
            )
        ) {
            throw new DomainException(
                'Only subject and concept merge proposals are supported.'
            );
        }
    }

    private function resolveEntities(
        CurriculumProposal $proposal
    ): array {
        return match ($proposal->entity_type) {
            'subject' => $this->resolveSubjects($proposal),
            'concept' => $this->resolveConcepts($proposal),

            default => throw new DomainException(
                'Unsupported merge proposal entity type.'
            ),
        };
    }

    private function resolveSubjects(
        CurriculumProposal $proposal
    ): array {
        [$sourceId, $targetId] =
            $this->resolveEntityIds($proposal);

        $source = Subject::find($sourceId);
        $target = Subject::find($targetId);

        if ($source === null || $target === null) {
            throw new DomainException(
                'Merge proposal references an unknown subject.'
            );
        }

        return [$source, $target];
    }

    private function resolveConcepts(
        CurriculumProposal $proposal
    ): array {
        [$sourceId, $targetId] =
            $this->resolveEntityIds($proposal);

        $source = Concept::find($sourceId);
        $target = Concept::find($targetId);

        if ($source === null || $target === null) {
            throw new DomainException(
                'Merge proposal references an unknown concept.'
            );
        }

        return [$source, $target];
    }

    private function resolveEntityIds(
        CurriculumProposal $proposal
    ): array {
        if ($proposal->entity_id === null) {
            throw new DomainException(
                'Merge proposal source entity is missing.'
            );
        }

        $targetId = $proposal->payload['duplicate_entity_id']
            ?? null;

        if (! is_int($targetId)) {
            throw new DomainException(
                'Merge proposal target entity is missing.'
            );
        }

        return [
            (int) $proposal->entity_id,
            $targetId,
        ];
    }
}
