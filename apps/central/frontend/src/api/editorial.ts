import { apiRequest } from './client'

import type { ApiResponse } from '../types/catalog'
import type {
  CreateResourcePayload,
  EditorialResource,
  EditorialResourceVersion,
  UpdateResourcePayload,
} from '../types/editorial'
import type { EducationalResourceDetail } from '../types/resource'

export async function getEditorialResources(): Promise<
  EditorialResource[]
> {
  const response = await apiRequest<
    ApiResponse<EditorialResource[]>
  >('/editor/resources')

  return response.data
}

export async function createEditorialResource(
  payload: CreateResourcePayload,
): Promise<EducationalResourceDetail> {
  const response = await apiRequest<
    ApiResponse<EducationalResourceDetail>
  >('/resources', {
    method: 'POST',
    body: JSON.stringify(payload),
  })

  return response.data
}

export async function updateEditorialResource(
  resourceId: number,
  payload: UpdateResourcePayload,
): Promise<EducationalResourceDetail> {
  const response = await apiRequest<
    ApiResponse<EducationalResourceDetail>
  >(`/resources/${resourceId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })

  return response.data
}

export async function submitResourceVersion(
  resourceVersionId: number,
): Promise<EditorialResourceVersion> {
  const response = await apiRequest<
    ApiResponse<EditorialResourceVersion>
  >(`/resource-versions/${resourceVersionId}/submit`, {
    method: 'POST',
  })

  return response.data
}

export async function reviseResourceVersion(
  resourceVersionId: number,
): Promise<EditorialResourceVersion> {
  const response = await apiRequest<
    ApiResponse<EditorialResourceVersion>
  >(`/resource-versions/${resourceVersionId}/revise`, {
    method: 'POST',
  })

  return response.data
}
