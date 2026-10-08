export type CurriculumEntityType =
  | 'subject'

export type CurriculumProposalType =
  | 'create'
  | 'update'
  | 'alias'
  | 'merge_candidate'

export type CurriculumProposalStatus =
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'merged'

export type CurriculumProposalPayload =
  Record<string, unknown> & {
    duplicate_entity_id?: number
  }

export interface CurriculumProposal {
  id: number
  entity_type: CurriculumEntityType
  entity_id: number | null
  proposal_type: CurriculumProposalType
  payload: CurriculumProposalPayload
  reason: string | null
  status: CurriculumProposalStatus
  proposed_by: number
  reviewed_by: number | null
  reviewed_at: string | null
  review_note: string | null
  created_at: string | null
  updated_at: string | null
}

export interface CreateCurriculumProposalPayload {
  entity_type: CurriculumEntityType
  entity_id?: number | null
  proposal_type: CurriculumProposalType
  payload: CurriculumProposalPayload
  reason?: string | null
}

export interface ReviewCurriculumProposalPayload {
  note?: string | null
}

export interface CurriculumMergePreview {
  source_subject_id: number
  target_subject_id: number
  curriculum_subjects_to_move: number
  curriculum_subject_collisions: number
  aliases_to_move: number
  aliases_to_deduplicate: number
  source_name_alias_will_be_created: boolean
  reputations_to_move: number
  reputations_to_consolidate: number
  reputation_events_to_move: number
  blocked: boolean
  blocking_reasons: string[]
}

export interface CurriculumMerge {
  id: number
  entity_type: CurriculumEntityType
  source_entity_id: number
  target_entity_id: number
  merged_by: number | null
  merged_at: string | null
  reason: string | null
  metadata: Record<string, unknown> | null
}

export interface CurriculumMergeResult {
  proposal: CurriculumProposal
  merge: CurriculumMerge
}
