export type EditorialStatus =
  | 'draft'
  | 'submitted'
  | 'rejected'
  | 'approved'
  | 'published'

export interface EditorialConcept {
  id: number
  code: string
  title: string
}

export interface EditorialResourceVersion {
  id: number
  resource_id: number
  version_number: number
  title: string
  summary: string | null
  content: string | null
  source_url: string | null
  language_code: string
  difficulty_level: number
  complexity_level: number
  status: EditorialStatus
  submitted_at: string | null
  reviewed_by: number | null
  reviewed_at: string | null
  review_note: string | null
  published_at: string | null
}

export interface EditorialResource {
  id: number
  code: string
  type: string
  status: string
  concept: EditorialConcept | null
  version: EditorialResourceVersion | null
}

export interface CreateResourcePayload {
  concept_id: number
  type: string
  title: string
  summary: string | null
  content: string | null
  source_url: string | null
  language_code: string
  difficulty_level: number
  complexity_level: number
}

export interface UpdateResourcePayload {
  concept_id?: number
  type?: string
  title?: string
  summary?: string | null
  content?: string | null
  source_url?: string | null
  language_code?: string
  difficulty_level?: number
  complexity_level?: number
}
