import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface AuthUser {
  id: number
  name: string
  email: string
  role: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)

  const isAuthenticated = () => user.value !== null

  function setUser(authenticatedUser: AuthUser | null) {
    user.value = authenticatedUser
  }

  return {
    user,
    isAuthenticated,
    setUser,
  }
})
