import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import {
  getCurrentUser,
  login as loginRequest,
  logout as logoutRequest,
} from '../api/auth'
import {
  clearStoredAuthToken,
  getStoredAuthToken,
  storeAuthToken,
} from '../auth/session'

import type { AuthenticatedUser } from '../types/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(getStoredAuthToken())
  const user = ref<AuthenticatedUser | null>(null)
  const initialized = ref(false)
  const loading = ref(false)

  const isAuthenticated = computed(
    () => token.value !== null && user.value !== null,
  )

  const canContribute = computed(() =>
    ['teacher', 'moderator', 'admin'].includes(
      user.value?.role ?? '',
    ),
  )

  const canModerate = computed(() =>
    ['moderator', 'admin'].includes(
      user.value?.role ?? '',
    ),
  )

  const canAdmin = computed(
    () => user.value?.role === 'admin',
  )

  function clearSession(): void {
    token.value = null
    user.value = null
    clearStoredAuthToken()
  }

  async function initialize(): Promise<void> {
    if (initialized.value) {
      return
    }

    if (!token.value) {
      initialized.value = true
      return
    }

    loading.value = true

    try {
      user.value = await getCurrentUser()
    } catch {
      clearSession()
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function login(
    email: string,
    password: string,
  ): Promise<void> {
    loading.value = true

    try {
      const result = await loginRequest({
        email,
        password,
        device_name: 'OpenEdu Web',
      })

      token.value = result.token
      user.value = result.user

      storeAuthToken(result.token)

      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    loading.value = true

    try {
      if (token.value) {
        await logoutRequest()
      }
    } catch {
      // Sesiunea locală trebuie eliminată chiar dacă tokenul
      // a expirat deja sau serverul nu mai este disponibil.
    } finally {
      clearSession()
      initialized.value = true
      loading.value = false
    }
  }

  return {
    token,
    user,
    initialized,
    loading,
    isAuthenticated,
    canContribute,
    canModerate,
    canAdmin,
    initialize,
    login,
    logout,
  }
})
