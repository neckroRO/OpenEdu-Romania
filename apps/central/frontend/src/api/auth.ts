import { apiRequest } from './client'

import type { ApiResponse } from '../types/catalog'
import type {
  AuthenticatedUser,
  LoginPayload,
  LoginResult,
} from '../types/auth'

export async function login(
  payload: LoginPayload,
): Promise<LoginResult> {
  const response = await apiRequest<ApiResponse<LoginResult>>(
    '/auth/login',
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  )

  return response.data
}

export async function getCurrentUser(): Promise<AuthenticatedUser> {
  const response =
    await apiRequest<ApiResponse<AuthenticatedUser>>('/auth/me')

  return response.data
}

export async function logout(): Promise<void> {
  await apiRequest('/auth/logout', {
    method: 'POST',
  })
}
