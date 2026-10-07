import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'
import {
  createPinia,
  setActivePinia,
} from 'pinia'

import * as authApi from '../src/api/auth'
import { useAuthStore } from '../src/stores/auth'

import type {
  AuthenticatedUser,
  UserRole,
} from '../src/types/auth'

vi.mock('../src/api/auth', () => ({
  getCurrentUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
}))

const mockedGetCurrentUser = vi.mocked(
  authApi.getCurrentUser,
)

const mockedLogin = vi.mocked(
  authApi.login,
)

const mockedLogout = vi.mocked(
  authApi.logout,
)

function makeUser(
  role: UserRole,
): AuthenticatedUser {
  return {
    id: 1,
    name: `Test ${role}`,
    email: `${role}@example.test`,
    role,
  }
}

describe('auth store', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initializează fără sesiune când nu există token', async () => {
    const store = useAuthStore()

    expect(store.initialized).toBe(false)

    await store.initialize()

    expect(store.initialized).toBe(true)
    expect(store.isAuthenticated).toBe(false)
    expect(store.token).toBeNull()
    expect(store.user).toBeNull()

    expect(
      mockedGetCurrentUser,
    ).not.toHaveBeenCalled()
  })

  it('restaurează utilizatorul dintr-o sesiune persistentă', async () => {
    localStorage.setItem(
      'openedu.auth.token',
      'saved-token',
    )

    const user = makeUser('teacher')

    mockedGetCurrentUser.mockResolvedValue(user)

    const store = useAuthStore()

    await store.initialize()

    expect(
      mockedGetCurrentUser,
    ).toHaveBeenCalledTimes(1)

    expect(store.token).toBe('saved-token')
    expect(store.user).toEqual(user)
    expect(store.initialized).toBe(true)
    expect(store.loading).toBe(false)
    expect(store.isAuthenticated).toBe(true)
  })

  it('elimină sesiunea dacă tokenul persistent nu mai este valid', async () => {
    localStorage.setItem(
      'openedu.auth.token',
      'expired-token',
    )

    mockedGetCurrentUser.mockRejectedValue(
      new Error('Unauthenticated'),
    )

    const store = useAuthStore()

    await store.initialize()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.initialized).toBe(true)
    expect(store.loading).toBe(false)

    expect(
      localStorage.getItem(
        'openedu.auth.token',
      ),
    ).toBeNull()
  })

  it('autentifică utilizatorul și persistă tokenul', async () => {
    const user = makeUser('teacher')

    mockedLogin.mockResolvedValue({
      token: 'login-token',
      token_type: 'Bearer',
      expires_at: '2026-12-31T23:59:59Z',
      user,
    })

    const store = useAuthStore()

    await store.login(
      'teacher@example.test',
      'secret-password',
    )

    expect(mockedLogin).toHaveBeenCalledWith({
      email: 'teacher@example.test',
      password: 'secret-password',
      device_name: 'OpenEdu Web',
    })

    expect(store.token).toBe('login-token')
    expect(store.user).toEqual(user)
    expect(store.isAuthenticated).toBe(true)
    expect(store.initialized).toBe(true)
    expect(store.loading).toBe(false)

    expect(
      localStorage.getItem(
        'openedu.auth.token',
      ),
    ).toBe('login-token')
  })

  it('curăță sesiunea locală la logout chiar dacă serverul răspunde cu eroare', async () => {
    localStorage.setItem(
      'openedu.auth.token',
      'logout-token',
    )

    const store = useAuthStore()

    store.user = makeUser('teacher')

    mockedLogout.mockRejectedValue(
      new Error('Token expired'),
    )

    await store.logout()

    expect(mockedLogout).toHaveBeenCalledTimes(1)

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.initialized).toBe(true)
    expect(store.loading).toBe(false)

    expect(
      localStorage.getItem(
        'openedu.auth.token',
      ),
    ).toBeNull()
  })

  it.each([
    ['learner', false, false, false],
    ['guardian', false, false, false],
    ['teacher', true, false, false],
    ['moderator', true, true, false],
    ['admin', true, true, true],
  ] as const)(
    'aplică permisiunile corecte pentru rolul %s',
    (
      role,
      canContribute,
      canModerate,
      canAdmin,
    ) => {
      const store = useAuthStore()

      store.token = 'role-token'
      store.user = makeUser(role)

      expect(store.isAuthenticated).toBe(true)
      expect(store.canContribute).toBe(
        canContribute,
      )
      expect(store.canModerate).toBe(
        canModerate,
      )

      expect(store.canAdmin).toBe(
        canAdmin,
      )
    },
  )
})
