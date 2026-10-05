import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'
import {
  flushPromises,
  mount,
} from '@vue/test-utils'
import {
  createPinia,
  setActivePinia,
} from 'pinia'
import {
  createMemoryHistory,
  createRouter,
} from 'vue-router'

import * as authApi from '../src/api/auth'
import { ApiError } from '../src/api/client'
import LoginView from '../src/views/LoginView.vue'

import type {
  AuthenticatedUser,
  UserRole,
} from '../src/types/auth'

vi.mock('../src/api/auth', () => ({
  getCurrentUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
}))

const mockedLogin = vi.mocked(authApi.login)

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

function loginResult(role: UserRole) {
  return {
    token: `${role}-token`,
    token_type: 'Bearer',
    expires_at: '2026-12-31T23:59:59Z',
    user: makeUser(role),
  }
}

async function mountLogin(
  initialPath = '/login',
) {
  const pinia = createPinia()
  setActivePinia(pinia)

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/',
        name: 'home',
        component: {
          template: '<div>Home</div>',
        },
      },
      {
        path: '/login',
        name: 'login',
        component: LoginView,
      },
      {
        path: '/teacher',
        name: 'teacher',
        component: {
          template: '<div>Teacher</div>',
        },
      },
      {
        path: '/moderation',
        name: 'moderation',
        component: {
          template: '<div>Moderation</div>',
        },
      },
    ],
  })

  await router.push(initialPath)
  await router.isReady()

  const wrapper = mount(LoginView, {
    global: {
      plugins: [
        pinia,
        router,
      ],
    },
  })

  return {
    wrapper,
    router,
  }
}

async function submitLogin(
  wrapper: ReturnType<typeof mount>,
  email = ' user@example.test ',
  password = 'secret-password',
) {
  await wrapper
    .get('input[type="email"]')
    .setValue(email)

  await wrapper
    .get('input[type="password"]')
    .setValue(password)

  await wrapper
    .get('form')
    .trigger('submit')

  await flushPromises()
}

describe('LoginView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('trimite learner către home după login', async () => {
    mockedLogin.mockResolvedValue(
      loginResult('learner'),
    )

    const {
      wrapper,
      router,
    } = await mountLogin()

    await submitLogin(wrapper)

    expect(
      router.currentRoute.value.name,
    ).toBe('home')
  })

  it('trimite guardian către home după login', async () => {
    mockedLogin.mockResolvedValue(
      loginResult('guardian'),
    )

    const {
      wrapper,
      router,
    } = await mountLogin()

    await submitLogin(wrapper)

    expect(
      router.currentRoute.value.name,
    ).toBe('home')
  })

  it('trimite teacher către zona teacher după login', async () => {
    mockedLogin.mockResolvedValue(
      loginResult('teacher'),
    )

    const {
      wrapper,
      router,
    } = await mountLogin()

    await submitLogin(wrapper)

    expect(
      router.currentRoute.value.name,
    ).toBe('teacher')
  })

  it.each([
    'moderator',
    'admin',
  ] as const)(
    'trimite rolul %s către moderation după login',
    async (role) => {
      mockedLogin.mockResolvedValue(
        loginResult(role),
      )

      const {
        wrapper,
        router,
      } = await mountLogin()

      await submitLogin(wrapper)

      expect(
        router.currentRoute.value.name,
      ).toBe('moderation')
    },
  )

  it('respectă redirectul cerut înainte de autentificare', async () => {
    mockedLogin.mockResolvedValue(
      loginResult('teacher'),
    )

    const {
      wrapper,
      router,
    } = await mountLogin(
      '/login?redirect=/teacher',
    )

    await submitLogin(wrapper)

    expect(
      router.currentRoute.value.fullPath,
    ).toBe('/teacher')
  })

  it('ignoră redirecturile care nu sunt căi locale', async () => {
    mockedLogin.mockResolvedValue(
      loginResult('teacher'),
    )

    const {
      wrapper,
      router,
    } = await mountLogin(
      '/login?redirect=https://example.com',
    )

    await submitLogin(wrapper)

    expect(
      router.currentRoute.value.name,
    ).toBe('teacher')
  })

  it('trimite către API e-mailul fără spații exterioare', async () => {
    mockedLogin.mockResolvedValue(
      loginResult('teacher'),
    )

    const {
      wrapper,
    } = await mountLogin()

    await submitLogin(
      wrapper,
      '  teacher@example.test  ',
      'secret-password',
    )

    expect(mockedLogin).toHaveBeenCalledWith({
      email: 'teacher@example.test',
      password: 'secret-password',
      device_name: 'OpenEdu Web',
    })
  })

  it.each([
    [
      401,
      'Adresa de e-mail sau parola nu sunt corecte.',
    ],
    [
      422,
      'Verifică datele introduse și încearcă din nou.',
    ],
    [
      429,
      'Prea multe încercări. Încearcă din nou puțin mai târziu.',
    ],
    [
      500,
      'Autentificarea nu a putut fi efectuată.',
    ],
  ] as const)(
    'afișează mesajul corect pentru eroarea HTTP %s',
    async (
      status,
      expectedMessage,
    ) => {
      mockedLogin.mockRejectedValue(
        new ApiError(status, null),
      )

      const {
        wrapper,
        router,
      } = await mountLogin()

      await submitLogin(wrapper)

      const alert = wrapper.get(
        '[role="alert"]',
      )

      expect(alert.text()).toBe(
        expectedMessage,
      )

      expect(
        router.currentRoute.value.name,
      ).toBe('login')
    },
  )
})
