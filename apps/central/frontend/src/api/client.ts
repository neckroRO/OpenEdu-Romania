import { getStoredAuthToken } from '../auth/session'

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ?? '/api/v1'

export class ApiError extends Error {
  public readonly status: number
  public readonly payload: unknown

  constructor(status: number, payload: unknown) {
    super(`API request failed with status ${status}`)

    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

export async function apiRequest<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const headers = new Headers(options.headers)

  headers.set('Accept', 'application/json')
  headers.set('Content-Type', 'application/json')

  const token = getStoredAuthToken()

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    throw new ApiError(response.status, payload)
  }

  return payload as T
}
