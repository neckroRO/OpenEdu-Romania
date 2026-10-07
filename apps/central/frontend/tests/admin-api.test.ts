import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import * as client from '../src/api/client'
import {
  createAdminUser,
  getAdminUsers,
  resetAdminUserPassword,
  updateAdminUser,
} from '../src/api/admin'

vi.mock('../src/api/client', () => ({
  apiRequest: vi.fn(),
}))

const mockedApiRequest = vi.mocked(
  client.apiRequest,
)

describe('admin API', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('încarcă utilizatorii centrali', async () => {
    mockedApiRequest.mockResolvedValue({
      data: [
        {
          id: 1,
          name: 'Administrator',
          email: 'admin@example.test',
          role: 'admin',
          is_active: true,
        },
      ],
      meta: {
        request_id: null,
      },
    })

    const result = await getAdminUsers()

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/admin/users',
    )

    expect(result).toHaveLength(1)

    expect(result[0]).toMatchObject({
      id: 1,
      role: 'admin',
      is_active: true,
    })
  })

  it('creează un utilizator', async () => {
    const payload = {
      name: 'Profesor Nou',
      email: 'profesor@example.test',
      role: 'teacher' as const,
      password: 'password123',
      password_confirmation: 'password123',
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        id: 2,
        name: payload.name,
        email: payload.email,
        role: payload.role,
        is_active: true,
      },
      meta: {
        request_id: null,
      },
    })

    const result = await createAdminUser(
      payload,
    )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/admin/users',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
    )

    expect(result.id).toBe(2)
  })

  it('actualizează un utilizator', async () => {
    const payload = {
      name: 'Profesor Actualizat',
      role: 'moderator' as const,
      is_active: false,
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        id: 7,
        name: payload.name,
        email: 'profesor@example.test',
        role: payload.role,
        is_active: false,
      },
      meta: {
        request_id: null,
      },
    })

    await updateAdminUser(
      7,
      payload,
    )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/admin/users/7',
      {
        method: 'PATCH',
        body: JSON.stringify(payload),
      },
    )
  })

  it('resetează parola unui utilizator', async () => {
    const payload = {
      password: 'new-password123',
      password_confirmation:
        'new-password123',
    }

    mockedApiRequest.mockResolvedValue({
      data: null,
      meta: {
        request_id: null,
      },
    })

    await resetAdminUserPassword(
      9,
      payload,
    )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/admin/users/9/password',
      {
        method: 'PUT',
        body: JSON.stringify(payload),
      },
    )
  })
})
