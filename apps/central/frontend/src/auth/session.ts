const AUTH_TOKEN_KEY = 'openedu.auth.token'

export function getStoredAuthToken(): string | null {
  return localStorage.getItem(AUTH_TOKEN_KEY)
}

export function storeAuthToken(token: string): void {
  localStorage.setItem(AUTH_TOKEN_KEY, token)
}

export function clearStoredAuthToken(): void {
  localStorage.removeItem(AUTH_TOKEN_KEY)
}
