export type UserRole =
  | 'learner'
  | 'guardian'
  | 'teacher'
  | 'moderator'
  | 'admin'

export interface AuthenticatedUser {
  id: number
  name: string
  email: string
  role: UserRole
}

export interface LoginPayload {
  email: string
  password: string
  device_name: string
}

export interface LoginResult {
  token: string
  token_type: string
  expires_at: string
  user: AuthenticatedUser
}
