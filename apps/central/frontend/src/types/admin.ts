export type AdminUserRole =
  | 'teacher'
  | 'moderator'
  | 'admin'

export interface AdminUser {
  id: number
  name: string
  email: string
  role: AdminUserRole
  is_active: boolean
}

export interface CreateAdminUserPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  role: AdminUserRole
}

export interface UpdateAdminUserPayload {
  name?: string
  email?: string
  role?: AdminUserRole
  is_active?: boolean
}

export interface ResetAdminUserPasswordPayload {
  password: string
  password_confirmation: string
}
