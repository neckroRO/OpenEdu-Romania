import type { ResourceVersionSummary } from './catalog'

export interface ResourceVersionDetail
  extends ResourceVersionSummary {
  content: string | null
  source_url: string | null
}

export interface EducationalResourceDetail {
  id: number
  code: string
  type: string
  version: ResourceVersionDetail | null
}

export type ResourceSortField =
  | 'published_at'
  | 'title'
  | 'difficulty_level'

export type ResourceSortDirection = 'asc' | 'desc'

export interface ResourceSearchParams {
  q?: string
  type?: string
  language_code?: string
  difficulty_level?: number
  complexity_level?: number
  page?: number
  per_page?: number
  sort?: ResourceSortField
  direction?: ResourceSortDirection
}

export interface ResourcePagination {
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
}

export interface ResourceListResult {
  data: EducationalResourceDetail[]
  pagination: ResourcePagination
}
