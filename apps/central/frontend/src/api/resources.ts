import { apiRequest } from './client'

import type { ApiResponse } from '../types/catalog'
import type { EducationalResourceDetail } from '../types/resource'

export async function getResource(
  resourceId: number,
): Promise<EducationalResourceDetail> {
  const response = await apiRequest<
    ApiResponse<EducationalResourceDetail>
  >(`/resources/${resourceId}`)

  return response.data
}
