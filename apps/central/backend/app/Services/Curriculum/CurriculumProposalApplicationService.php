<?php

namespace App\Services\Curriculum;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\Concept;
use App\Models\CurriculumAlias;
use App\Models\CurriculumProposal;
use App\Models\Domain;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CurriculumProposalApplicationService
{
    public function __construct(
        private readonly CurriculumProposalWorkflow $workflow,
        private readonly CurriculumAuditService $auditService,
        private readonly CurriculumTextNormalizer $normalizer
    ) {
    }

    public function apply(
        CurriculumProposal $proposal,
        User $reviewer,
        ?string $note = null
    ): CurriculumProposal {
        if ($proposal->status !== CurriculumProposalStatus::Pending) {
            throw new DomainException(
                'Only pending curriculum proposals can be applied.'
            );
        }

        if (! in_array(
            $proposal->entity_type,
            ['domain', 'concept'],
            true
        )) {
            throw new DomainException(
                'This proposal entity type is not supported by this application flow.'
            );
        }

        return DB::transaction(function () use (
            $proposal,
            $reviewer,
            $note
        ): CurriculumProposal {
            $entity = match ($proposal->entity_type) {
                'domain' => $this->applyDomainProposal(
                    $proposal
                ),
                'concept' => $this->applyConceptProposal(
                    $proposal,
                    $reviewer
                ),
                default => throw new DomainException(
                    'Unsupported curriculum proposal entity type.'
                ),
            };

            $appliedEntityId = $this->resolveAppliedEntityId(
                $proposal,
                $entity
            );

            if (
                $proposal->proposal_type
                === CurriculumProposalType::Create
            ) {
                $proposal->update([
                    'entity_id' => $appliedEntityId,
                ]);
            }

            $approved = $this->workflow->approve(
                $proposal->refresh(),
                $reviewer,
                $note
            );

            $this->auditService->record(
                eventType: 'proposal_applied',
                actor: $reviewer,
                proposal: $approved,
                entityType: $proposal->entity_type,
                entityId: $appliedEntityId,
                metadata: [
                    'proposal_type' =>
                        $proposal->proposal_type->value,
                    'review_note' => $note,
                ]
            );

            return $approved->refresh();
        });
    }

    private function resolveAppliedEntityId(
        CurriculumProposal $proposal,
        Model $entity
    ): int {
        if (
            $proposal->entity_type === 'concept'
            && $proposal->proposal_type
                === CurriculumProposalType::Alias
        ) {
            return (int) $proposal->entity_id;
        }

        return (int) $entity->getKey();
    }

    private function applyDomainProposal(
        CurriculumProposal $proposal
    ): Domain {
        return match ($proposal->proposal_type) {
            CurriculumProposalType::Create =>
                $this->createDomain($proposal),

            CurriculumProposalType::Update =>
                $this->updateDomain($proposal),

            default => throw new DomainException(
                'Unsupported domain proposal type.'
            ),
        };
    }

    private function applyConceptProposal(
        CurriculumProposal $proposal,
        User $reviewer
    ): Model {
        return match ($proposal->proposal_type) {
            CurriculumProposalType::Create =>
                $this->createConcept($proposal),

            CurriculumProposalType::Update =>
                $this->updateConcept($proposal),

            CurriculumProposalType::Alias =>
                $this->createConceptAlias(
                    $proposal,
                    $reviewer
                ),

            default => throw new DomainException(
                'Unsupported concept proposal type.'
            ),
        };
    }

    private function createDomain(
        CurriculumProposal $proposal
    ): Domain {
        return Domain::create([
            'curriculum_subject_id' =>
                $proposal->payload['curriculum_subject_id'],
            'parent_domain_id' =>
                $proposal->payload['parent_domain_id'] ?? null,
            'title' =>
                $proposal->payload['title'],
            'description' =>
                $proposal->payload['description'] ?? null,
            'display_order' =>
                $proposal->payload['display_order'] ?? 0,
        ]);
    }

    private function updateDomain(
        CurriculumProposal $proposal
    ): Domain {
        $domain = Domain::query()
            ->find($proposal->entity_id);

        if ($domain === null) {
            throw new DomainException(
                'The proposed domain no longer exists.'
            );
        }

        $domain->fill($proposal->payload);
        $domain->save();

        return $domain->refresh();
    }

    private function createConcept(
        CurriculumProposal $proposal
    ): Concept {
        return Concept::create([
            'code' =>
                $proposal->payload['code'],
            'title' =>
                $proposal->payload['title'],
            'description' =>
                $proposal->payload['description'] ?? null,
            'status' => 'active',
        ]);
    }

    private function updateConcept(
        CurriculumProposal $proposal
    ): Concept {
        $concept = Concept::query()
            ->find($proposal->entity_id);

        if ($concept === null) {
            throw new DomainException(
                'The proposed concept no longer exists.'
            );
        }

        $concept->fill($proposal->payload);
        $concept->save();

        return $concept->refresh();
    }

    private function createConceptAlias(
        CurriculumProposal $proposal,
        User $reviewer
    ): CurriculumAlias {
        $concept = Concept::query()
            ->find($proposal->entity_id);

        if ($concept === null) {
            throw new DomainException(
                'The proposed concept no longer exists.'
            );
        }

        $alias = trim(
            (string) $proposal->payload['alias']
        );

        $normalizedAlias = $this->normalizer->normalize(
            $alias
        );

        if ($normalizedAlias === '') {
            throw new DomainException(
                'The concept alias cannot be empty.'
            );
        }

        $exists = CurriculumAlias::query()
            ->where('entity_type', 'concept')
            ->where('entity_id', $concept->id)
            ->where(
                'normalized_alias',
                $normalizedAlias
            )
            ->exists();

        if ($exists) {
            throw new DomainException(
                'This concept alias already exists.'
            );
        }

        return CurriculumAlias::create([
            'entity_type' => 'concept',
            'entity_id' => $concept->id,
            'alias' => $alias,
            'normalized_alias' => $normalizedAlias,
            'source' => 'community',
            'status' => 'approved',
            'created_by' => $proposal->proposed_by,
            'approved_by' => $reviewer->id,
        ]);
    }
}
