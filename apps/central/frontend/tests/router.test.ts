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
import router from '../src/router'
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

function authenticateAs(
  role: UserRole,
): void {
  const store = useAuthStore()

  store.token = `${role}-token`
  store.user = makeUser(role)
  store.initialized = true
}

describe('route guards', () => {
  beforeEach(async () => {
    localStorage.clear()
    vi.clearAllMocks()

    setActivePinia(createPinia())

    const store = useAuthStore()
    store.initialized = true

    await router.replace('/')
  })

  describe('rute publice', () => {
    it.each([
      '/',
      '/resources',
      '/resources/123',
    ])(
      'permite accesul anonim la %s',
      async (path) => {
        await router.push(path)

        expect(
          router.currentRoute.value.fullPath,
        ).toBe(path)
      },
    )
  })

  describe('/teacher', () => {
    it('trimite vizitatorul neautentificat la login', async () => {
      await router.push('/teacher')

      expect(
        router.currentRoute.value.name,
      ).toBe('login')

      expect(
        router.currentRoute.value.query,
      ).toEqual({
        redirect: '/teacher',
      })
    })

    it.each([
      'learner',
      'guardian',
    ] as const)(
      'refuză accesul rolului %s și îl trimite acasă',
      async (role) => {
        authenticateAs(role)

        await router.push('/teacher')

        expect(
          router.currentRoute.value.name,
        ).toBe('home')
      },
    )

    it.each([
      'teacher',
      'moderator',
      'admin',
    ] as const)(
      'permite accesul rolului %s',
      async (role) => {
        authenticateAs(role)

        await router.push('/teacher')

        expect(
          router.currentRoute.value.name,
        ).toBe('teacher')
      },
    )
  })

describe('/curriculum', () => {
  it('trimite vizitatorul neautentificat la login', async () => {
    await router.push('/curriculum')

    expect(
      router.currentRoute.value.name,
    ).toBe('login')

    expect(
      router.currentRoute.value.query,
    ).toEqual({
      redirect: '/curriculum',
    })
  })

  it.each([
    'learner',
    'guardian',
  ] as const)(
    'refuză accesul rolului %s și îl trimite acasă',
    async (role) => {
      authenticateAs(role)

      await router.push('/curriculum')

      expect(
        router.currentRoute.value.name,
      ).toBe('home')
    },
  )

  it.each([
    'teacher',
    'moderator',
    'admin',
  ] as const)(
    'permite accesul rolului %s',
    async (role) => {
      authenticateAs(role)

      await router.push('/curriculum')

      expect(
        router.currentRoute.value.name,
      ).toBe('curriculum')
    },
  )
})

  describe('/moderation', () => {
    it('trimite vizitatorul neautentificat la login', async () => {
      await router.push('/moderation')

      expect(
        router.currentRoute.value.name,
      ).toBe('login')

      expect(
        router.currentRoute.value.query,
      ).toEqual({
        redirect: '/moderation',
      })
    })

    it.each([
      'learner',
      'guardian',
    ] as const)(
      'refuză accesul rolului %s și îl trimite acasă',
      async (role) => {
        authenticateAs(role)

        await router.push('/moderation')

        expect(
          router.currentRoute.value.name,
        ).toBe('home')
      },
    )

    it('trimite profesorul către zona teacher', async () => {
      authenticateAs('teacher')

      await router.push('/moderation')

      expect(
        router.currentRoute.value.name,
      ).toBe('teacher')
    })

    it.each([
      'moderator',
      'admin',
    ] as const)(
      'permite accesul rolului %s',
      async (role) => {
        authenticateAs(role)

        await router.push('/moderation')

        expect(
          router.currentRoute.value.name,
        ).toBe('moderation')
      },
    )
  })

  describe('/admin/users', () => {
    it('trimite vizitatorul neautentificat la login', async () => {
      await router.push('/admin/users')

      expect(
        router.currentRoute.value.name,
      ).toBe('login')

      expect(
        router.currentRoute.value.query,
      ).toEqual({
        redirect: '/admin/users',
      })
    })

    it.each([
      'learner',
      'guardian',
    ] as const)(
      'refuză accesul rolului %s și îl trimite acasă',
      async (role) => {
        authenticateAs(role)

        await router.push('/admin/users')

        expect(
          router.currentRoute.value.name,
        ).toBe('home')
      },
    )

    it('trimite profesorul către zona teacher', async () => {
      authenticateAs('teacher')

      await router.push('/admin/users')

      expect(
        router.currentRoute.value.name,
      ).toBe('teacher')
    })

    it('trimite moderatorul către zona moderation', async () => {
      authenticateAs('moderator')

      await router.push('/admin/users')

      expect(
        router.currentRoute.value.name,
      ).toBe('moderation')
    })

    it('permite accesul administratorului', async () => {
      authenticateAs('admin')

      await router.push('/admin/users')

      expect(
        router.currentRoute.value.name,
      ).toBe('admin-users')
    })
  })

  describe('/login pentru utilizatori autentificați', () => {
    it.each([
      'learner',
      'guardian',
    ] as const)(
      'trimite rolul %s către home',
      async (role) => {
        authenticateAs(role)

        await router.push('/login')

        expect(
          router.currentRoute.value.name,
        ).toBe('home')
      },
    )

    it('trimite profesorul către teacher', async () => {
      authenticateAs('teacher')

      await router.push('/login')

      expect(
        router.currentRoute.value.name,
      ).toBe('teacher')
    })

    it.each([
      'moderator',
      'admin',
    ] as const)(
      'trimite rolul %s către moderation',
      async (role) => {
        authenticateAs(role)

        await router.push('/login')

        expect(
          router.currentRoute.value.name,
        ).toBe('moderation')
      },
    )
  })

  it('invalidează tokenul expirat înainte de accesarea unei rute protejate', async () => {
    setActivePinia(createPinia())

    localStorage.setItem(
      'openedu.auth.token',
      'expired-token',
    )

    mockedGetCurrentUser.mockRejectedValue(
      new Error('Unauthenticated'),
    )

    await router.push('/teacher')

    expect(
      mockedGetCurrentUser,
    ).toHaveBeenCalledTimes(1)

    expect(
      router.currentRoute.value.name,
    ).toBe('login')

    expect(
      router.currentRoute.value.query,
    ).toEqual({
      redirect: '/teacher',
    })

    expect(
      localStorage.getItem(
        'openedu.auth.token',
      ),
    ).toBeNull()

    const store = useAuthStore()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })
})
