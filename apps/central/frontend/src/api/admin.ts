import { apiRequest } from './client'

import type { ApiResponse } from '../types/catalog'
import type {
  AdminUser,
  CreateAdminUserPayload,
  ResetAdminUserPasswordPayload,
  UpdateAdminUserPayload,
} from '../types/admin'

export async function getAdminUsers(): Promise<
  AdminUser[]
> {
  const response = await apiRequest<
    ApiResponse<AdminUser[]>
  >('/admin/users')

  return response.data
}

export async function createAdminUser(
  payload: CreateAdminUserPayload,
): Promise<AdminUser> {
  const response = await apiRequest<
    ApiResponse<AdminUser>
  >('/admin/users', {
    method: 'POST',
    body: JSON.stringify(payload),
  })

  return response.data
}

export async function updateAdminUser(
  userId: number,
  payload: UpdateAdminUserPayload,
): Promise<AdminUser> {
  const response = await apiRequest<
    ApiResponse<AdminUser>
  >(`/admin/users/${userId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })

  return response.data
}

export async function resetAdminUserPassword(
  userId: number,
  payload: ResetAdminUserPasswordPayload,
): Promise<void> {
  await apiRequest(
    `/admin/users/${userId}/password`,
    {
      method: 'PUT',
      body: JSON.stringify(payload),
    },
  )
}
