export interface ApiMeta {
  request_id: string | null
}

export interface ApiResponse<T> {
  data: T
  meta: ApiMeta
}

export interface EducationLevel {
  id: number
  code: string
  name: string
  ordinal: number
  education_stage: string | null
}

export interface Subject {
  id: number
  code: string
  name: string
  description: string | null
}

export interface CurriculumSubject {
  id: number
  display_order: number
  subject: Subject
}

export interface Domain {
  id: number
  title: string
  description: string | null
  display_order: number
}

export interface Concept {
  id: number
  code: string
  title: string
  description: string | null
}

export interface ConceptPlacement {
  id: number
  display_order: number
  is_core: boolean
  domain: Domain
  concept: Concept
}

export interface ResourceVersionSummary {
  id: number
  version_number: number
  title: string
  summary: string | null
  language_code: string
  difficulty_level: number
  complexity_level: number
  published_at: string | null
}

export interface CatalogResource {
  id: number
  code: string
  type: string
  version: ResourceVersionSummary | null
}

export interface ConceptResourceLink {
  id: number
  is_primary: boolean
  display_order: number
  resource: CatalogResource
}
