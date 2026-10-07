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

import * as adminApi from '../src/api/admin'
import { useAuthStore } from '../src/stores/auth'
import AdminUsersView from '../src/views/AdminUsersView.vue'

import type { AdminUser } from '../src/types/admin'

vi.mock('../src/api/admin', () => ({
  getAdminUsers: vi.fn(),
  createAdminUser: vi.fn(),
  updateAdminUser: vi.fn(),
  resetAdminUserPassword: vi.fn(),
}))

const mockedGetAdminUsers = vi.mocked(
  adminApi.getAdminUsers,
)

const mockedCreateAdminUser = vi.mocked(
  adminApi.createAdminUser,
)

const mockedUpdateAdminUser = vi.mocked(
  adminApi.updateAdminUser,
)

const mockedResetAdminUserPassword = vi.mocked(
  adminApi.resetAdminUserPassword,
)

function makeUser(
  id: number,
  overrides: Partial<AdminUser> = {},
): AdminUser {
  return {
    id,
    name: `Utilizator ${id}`,
    email: `user${id}@example.test`,
    role: 'teacher',
    is_active: true,
    ...overrides,
  }
}

async function mountAdminUsers() {
  const pinia = createPinia()
  setActivePinia(pinia)

  const authStore = useAuthStore()

  authStore.token = 'admin-token'
  authStore.user = {
    id: 1,
    name: 'Administrator',
    email: 'admin@example.test',
    role: 'admin',
  }
  authStore.initialized = true

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin/users',
        name: 'admin-users',
        component: {
          template: '<div>Admin</div>',
        },
      },
      {
        path: '/login',
        name: 'login',
        component: {
          template: '<div>Login</div>',
        },
      },
    ],
  })

  await router.push('/admin/users')
  await router.isReady()

  const wrapper = mount(
    AdminUsersView,
    {
      global: {
        plugins: [
          pinia,
          router,
        ],
      },
    },
  )

  await flushPromises()

  return {
    wrapper,
    router,
    authStore,
  }
}

describe('AdminUsersView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()

    mockedGetAdminUsers
      .mockResolvedValue([])

    mockedResetAdminUserPassword
      .mockResolvedValue(undefined)
  })

  it('încarcă utilizatorii și afișează statisticile', async () => {
    mockedGetAdminUsers
      .mockResolvedValue([
        makeUser(1, {
          name: 'Administrator',
          email: 'admin@example.test',
          role: 'admin',
        }),
        makeUser(2, {
          role: 'teacher',
          is_active: false,
        }),
        makeUser(3, {
          role: 'moderator',
        }),
      ])

    const {
      wrapper,
    } = await mountAdminUsers()

    expect(
      mockedGetAdminUsers,
    ).toHaveBeenCalledTimes(1)

    expect(
      wrapper.findAll('.user-card'),
    ).toHaveLength(3)

    const stats =
      wrapper.findAll('.stat-card')

    expect(stats[0]!.text()).toContain('3')
    expect(stats[1]!.text()).toContain('2')
    expect(stats[2]!.text()).toContain('1')
    expect(stats[3]!.text()).toContain('1')
    expect(stats[4]!.text()).toContain('1')

    expect(
      wrapper
        .get('.user-card')
        .classes(),
    ).toContain('selected')

    expect(
      wrapper
        .get('.details-grid')
        .text(),
    ).toContain('Administrator')
  })

  it('creează un utilizator și reîncarcă lista', async () => {
    const current = makeUser(1, {
      name: 'Administrator',
      email: 'admin@example.test',
      role: 'admin',
    })

    const created = makeUser(2, {
      name: 'Profesor Nou',
      email: 'profesor@example.test',
      role: 'moderator',
    })

    mockedGetAdminUsers
      .mockResolvedValueOnce([
        current,
      ])
      .mockResolvedValueOnce([
        current,
        created,
      ])

    mockedCreateAdminUser
      .mockResolvedValue(created)

    const {
      wrapper,
    } = await mountAdminUsers()

    const form =
      wrapper.get('.create-panel form')

    await form
      .get('input[type="text"]')
      .setValue('  Profesor Nou  ')

    await form
      .get('input[type="email"]')
      .setValue(
        '  PROFESOR@EXAMPLE.TEST  ',
      )

    await form
      .get('select')
      .setValue('moderator')

    const passwords =
      form.findAll(
        'input[type="password"]',
      )

    await passwords[0]!.setValue(
      'password123',
    )

    await passwords[1]!.setValue(
      'password123',
    )

    await form.trigger('submit')
    await flushPromises()

    expect(
      mockedCreateAdminUser,
    ).toHaveBeenCalledWith({
      name: 'Profesor Nou',
      email: 'profesor@example.test',
      role: 'moderator',
      password: 'password123',
      password_confirmation:
        'password123',
    })

    expect(
      mockedGetAdminUsers,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper
        .get('[role="status"]')
        .text(),
    ).toContain(
      'Utilizatorul Profesor Nou a fost creat.',
    )

    expect(
      wrapper
        .findAll('.user-card'),
    ).toHaveLength(2)
  })

  it('nu trimite rolul și starea când administratorul își editează propriul cont', async () => {
    const current = makeUser(1, {
      name: 'Administrator',
      email: 'admin@example.test',
      role: 'admin',
    })

    const updated = {
      ...current,
      name: 'Administrator Actualizat',
      email: 'nou@example.test',
    }

    mockedGetAdminUsers
      .mockResolvedValueOnce([
        current,
      ])
      .mockResolvedValueOnce([
        updated,
      ])

    mockedUpdateAdminUser
      .mockResolvedValue(updated)

    const {
      wrapper,
    } = await mountAdminUsers()

    const panel =
      wrapper.findAll(
        '.details-grid .panel',
      )[0]!

    expect(
      panel.get('select').attributes(
        'disabled',
      ),
    ).toBeDefined()

    expect(
      panel
        .get('input[type="checkbox"]')
        .attributes('disabled'),
    ).toBeDefined()

    await panel
      .get('input[type="text"]')
      .setValue(
        ' Administrator Actualizat ',
      )

    await panel
      .get('input[type="email"]')
      .setValue(
        ' NOU@EXAMPLE.TEST ',
      )

    await panel
      .get('form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedUpdateAdminUser,
    ).toHaveBeenCalledWith(
      1,
      {
        name: 'Administrator Actualizat',
        email: 'nou@example.test',
      },
    )
  })

  it('editează rolul și starea altui utilizator', async () => {
    const current = makeUser(1, {
      name: 'Administrator',
      email: 'admin@example.test',
      role: 'admin',
    })

    const teacher = makeUser(2, {
      name: 'Profesor',
      role: 'teacher',
      is_active: true,
    })

    const updated = {
      ...teacher,
      name: 'Profesor Actualizat',
      role: 'moderator' as const,
      is_active: false,
    }

    mockedGetAdminUsers
      .mockResolvedValueOnce([
        current,
        teacher,
      ])
      .mockResolvedValueOnce([
        current,
        updated,
      ])

    mockedUpdateAdminUser
      .mockResolvedValue(updated)

    const {
      wrapper,
    } = await mountAdminUsers()

    const cards =
      wrapper.findAll('.user-card')

    await cards[1]!.trigger('click')

    const panel =
      wrapper.findAll(
        '.details-grid .panel',
      )[0]!

    await panel
      .get('input[type="text"]')
      .setValue(
        ' Profesor Actualizat ',
      )

    await panel
      .get('select')
      .setValue('moderator')

    await panel
      .get('input[type="checkbox"]')
      .setValue(false)

    await panel
      .get('form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedUpdateAdminUser,
    ).toHaveBeenCalledWith(
      2,
      {
        name: 'Profesor Actualizat',
        email: teacher.email,
        role: 'moderator',
        is_active: false,
      },
    )

    expect(
      mockedGetAdminUsers,
    ).toHaveBeenCalledTimes(2)
  })

  it('resetează parola utilizatorului selectat', async () => {
    const target = makeUser(2, {
      name: 'Profesor',
      role: 'teacher',
    })

    mockedGetAdminUsers
      .mockResolvedValue([
        target,
      ])

    const {
      wrapper,
    } = await mountAdminUsers()

    const panel =
      wrapper.findAll(
        '.details-grid .panel',
      )[1]!

    const passwords =
      panel.findAll(
        'input[type="password"]',
      )

    await passwords[0]!.setValue(
      'new-password123',
    )

    await passwords[1]!.setValue(
      'new-password123',
    )

    await panel
      .get('form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedResetAdminUserPassword,
    ).toHaveBeenCalledWith(
      2,
      {
        password: 'new-password123',
        password_confirmation:
          'new-password123',
      },
    )

    expect(
      wrapper
        .get('[role="status"]')
        .text(),
    ).toContain(
      'Parola pentru Profesor a fost resetată.',
    )
  })

  it('refuză parolele cu confirmare diferită', async () => {
    mockedGetAdminUsers
      .mockResolvedValue([
        makeUser(2),
      ])

    const {
      wrapper,
    } = await mountAdminUsers()

    const panel =
      wrapper.findAll(
        '.details-grid .panel',
      )[1]!

    const passwords =
      panel.findAll(
        'input[type="password"]',
      )

    await passwords[0]!.setValue(
      'password123',
    )

    await passwords[1]!.setValue(
      'different123',
    )

    await panel
      .get('form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedResetAdminUserPassword,
    ).not.toHaveBeenCalled()

    expect(
      panel
        .get('[role="alert"]')
        .text(),
    ).toBe(
      'Confirmarea parolei nu corespunde.',
    )
  })
})
