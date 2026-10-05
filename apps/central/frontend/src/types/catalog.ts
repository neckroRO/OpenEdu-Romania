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
