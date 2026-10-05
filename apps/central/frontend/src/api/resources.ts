import { apiRequest } from './client'

import type { ApiResponse } from '../types/catalog'
import type {
  EducationalResourceDetail,
  ResourceListResult,
  ResourcePagination,
  ResourceSearchParams,
} from '../types/resource'

interface ResourceListApiResponse {
  data: EducationalResourceDetail[]
  meta: {
    request_id: string | null
    pagination: ResourcePagination
  }
}

export async function getResource(
  resourceId: number,
): Promise<EducationalResourceDetail> {
  const response = await apiRequest<
    ApiResponse<EducationalResourceDetail>
  >(`/resources/${resourceId}`)

  return response.data
}

export async function getResources(
  params: ResourceSearchParams = {},
): Promise<ResourceListResult> {
  const query = new URLSearchParams()

  if (params.q) {
    query.set('q', params.q)
  }

  if (params.type) {
    query.set('type', params.type)
  }

  if (params.language_code) {
    query.set('language_code', params.language_code)
  }

  if (params.difficulty_level) {
    query.set(
      'difficulty_level',
      String(params.difficulty_level),
    )
  }

  if (params.complexity_level) {
    query.set(
      'complexity_level',
      String(params.complexity_level),
    )
  }

  if (params.page) {
    query.set('page', String(params.page))
  }

  if (params.per_page) {
    query.set('per_page', String(params.per_page))
  }

  if (params.sort) {
    query.set('sort', params.sort)
  }

  if (params.direction) {
    query.set('direction', params.direction)
  }

  const suffix = query.toString()
    ? `?${query.toString()}`
    : ''

  const response =
    await apiRequest<ResourceListApiResponse>(
      `/resources${suffix}`,
    )

  return {
    data: response.data,
    pagination: response.meta.pagination,
  }
}
