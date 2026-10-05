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
