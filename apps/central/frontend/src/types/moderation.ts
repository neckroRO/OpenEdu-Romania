import type { EditorialConcept } from './editorial'

export type ModerationStatus =
  | 'submitted'
  | 'approved'

export interface ModerationResourceSummary {
  id: number
  code: string
  type: string
  status: string
}

export interface ModerationCreator {
  id: number
  name: string
  email: string
}

export interface ModerationQueueItem {
  id: number
  resource: ModerationResourceSummary
  concept: EditorialConcept | null
  creator: ModerationCreator | null
  version_number: number
  title: string
  summary: string | null
  content: string | null
  source_url: string | null
  language_code: string
  difficulty_level: number
  complexity_level: number
  status: ModerationStatus
  submitted_at: string | null
  reviewed_by: number | null
  reviewed_at: string | null
  review_note: string | null
  published_at: string | null
}
