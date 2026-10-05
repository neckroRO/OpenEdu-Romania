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

import * as editorialApi from '../src/api/editorial'
import { ApiError } from '../src/api/client'
import ModerationView from '../src/views/ModerationView.vue'

import type {
  ModerationQueueItem,
  ModerationStatus,
} from '../src/types/moderation'

vi.mock('../src/api/editorial', () => ({
  getModerationQueue: vi.fn(),
  approveResourceVersion: vi.fn(),
  rejectResourceVersion: vi.fn(),
  publishResourceVersion: vi.fn(),
}))

const mockedGetModerationQueue = vi.mocked(
  editorialApi.getModerationQueue,
)

const mockedApproveResourceVersion = vi.mocked(
  editorialApi.approveResourceVersion,
)

const mockedRejectResourceVersion = vi.mocked(
  editorialApi.rejectResourceVersion,
)

const mockedPublishResourceVersion = vi.mocked(
  editorialApi.publishResourceVersion,
)

function queueItem(
  id = 101,
  status: ModerationStatus = 'submitted',
): ModerationQueueItem {
  return {
    id,
    resource: {
      id: id - 100,
      code: `RES-${id - 100}`,
      type: 'lesson',
      status: 'active',
    },
    concept: {
      id: 200,
      code: 'CON-200',
      title: 'Fracții',
    },
    creator: {
      id: 7,
      name: `Profesor ${id}`,
      email: `profesor${id}@example.com`,
    },
    version_number: 1,
    title: `Material ${id}`,
    summary: `Rezumat ${id}`,
    content: `Conținut ${id}`,
    source_url: 'https://example.com/source',
    language_code: 'ro',
    difficulty_level: 3,
    complexity_level: 4,
    status,
    submitted_at: '2026-10-01T10:00:00Z',
    reviewed_by:
      status === 'approved'
        ? 9
        : null,
    reviewed_at:
      status === 'approved'
        ? '2026-10-02T10:00:00Z'
        : null,
    review_note: null,
    published_at: null,
  }
}

async function mountModeration() {
  const pinia = createPinia()
  setActivePinia(pinia)

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/moderation',
        name: 'moderation',
        component: {
          template: '<div>Moderation</div>',
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

  await router.push('/moderation')
  await router.isReady()

  const wrapper = mount(
    ModerationView,
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
  }
}

describe('ModerationView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()

    mockedGetModerationQueue
      .mockResolvedValue([])

    mockedApproveResourceVersion
      .mockResolvedValue(undefined)

    mockedRejectResourceVersion
      .mockResolvedValue(undefined)

    mockedPublishResourceVersion
      .mockResolvedValue(undefined)
  })

  it('încarcă coada și selectează implicit prima resursă', async () => {
    mockedGetModerationQueue
      .mockResolvedValue([
        queueItem(101, 'submitted'),
        queueItem(102, 'approved'),
        queueItem(103, 'submitted'),
      ])

    const {
      wrapper,
    } = await mountModeration()

    expect(
      mockedGetModerationQueue,
    ).toHaveBeenCalledTimes(1)

    const stats =
      wrapper.findAll(
        '.moderation-stats > div',
      )

    expect(stats[0]!.text()).toContain(
      '3',
    )
    expect(stats[1]!.text()).toContain(
      '2',
    )
    expect(stats[2]!.text()).toContain(
      '1',
    )

    expect(
      wrapper
        .get('.moderation-preview')
        .text(),
    ).toContain(
      'Material 101',
    )

    expect(
      wrapper
        .get('.queue-card')
        .classes(),
    ).toContain('selected')
  })

  it('schimbă materialul selectat din coadă', async () => {
    mockedGetModerationQueue
      .mockResolvedValue([
        queueItem(101, 'submitted'),
        queueItem(102, 'approved'),
      ])

    const {
      wrapper,
    } = await mountModeration()

    const cards =
      wrapper.findAll('.queue-card')

    await cards[1]!.trigger('click')

    expect(
      wrapper
        .get('.moderation-preview')
        .text(),
    ).toContain(
      'Material 102',
    )

    expect(
      cards[1]!.classes(),
    ).toContain('selected')

    expect(
      wrapper.find(
        '.publish-button',
      ).exists(),
    ).toBe(true)
  })

  it('aprobă o resursă trimisă și reîncarcă lista', async () => {
    const submitted =
      queueItem(101, 'submitted')

    const approved =
      queueItem(101, 'approved')

    mockedGetModerationQueue
      .mockResolvedValueOnce([
        submitted,
      ])
      .mockResolvedValueOnce([
        approved,
      ])

    const {
      wrapper,
    } = await mountModeration()

    await wrapper
      .get('.approve-button')
      .trigger('click')

    await flushPromises()

    expect(
      mockedApproveResourceVersion,
    ).toHaveBeenCalledWith(101)

    expect(
      mockedGetModerationQueue,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.get(
        '[role="status"]',
      ).text(),
    ).toBe(
      'Resursa a fost aprobată și este pregătită pentru publicare.',
    )

    expect(
      wrapper.find(
        '.publish-button',
      ).exists(),
    ).toBe(true)
  })

  it('nu permite respingerea fără feedback', async () => {
    mockedGetModerationQueue
      .mockResolvedValue([
        queueItem(),
      ])

    const {
      wrapper,
    } = await mountModeration()

    await wrapper
      .get('.reject-button')
      .trigger('click')

    await flushPromises()

    expect(
      mockedRejectResourceVersion,
    ).not.toHaveBeenCalled()

    expect(
      wrapper.get(
        '[role="alert"]',
      ).text(),
    ).toBe(
      'Feedback-ul este obligatoriu pentru respingere.',
    )
  })

  it('respinge resursa cu feedback normalizat', async () => {
    mockedGetModerationQueue
      .mockResolvedValueOnce([
        queueItem(),
      ])
      .mockResolvedValueOnce([])

    const {
      wrapper,
    } = await mountModeration()

    await wrapper
      .get(
        'textarea[placeholder^="Completează feedback-ul"]',
      )
      .setValue(
        '  Completează explicația exemplelor.  ',
      )

    await wrapper
      .get('.reject-button')
      .trigger('click')

    await flushPromises()

    expect(
      mockedRejectResourceVersion,
    ).toHaveBeenCalledWith(
      101,
      'Completează explicația exemplelor.',
    )

    expect(
      mockedGetModerationQueue,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.get(
        '[role="status"]',
      ).text(),
    ).toBe(
      'Resursa a fost returnată autorului cu feedback.',
    )
  })

  it('publică o resursă aprobată', async () => {
    mockedGetModerationQueue
      .mockResolvedValueOnce([
        queueItem(
          102,
          'approved',
        ),
      ])
      .mockResolvedValueOnce([])

    const {
      wrapper,
    } = await mountModeration()

    await wrapper
      .get('.publish-button')
      .trigger('click')

    await flushPromises()

    expect(
      mockedPublishResourceVersion,
    ).toHaveBeenCalledWith(102)

    expect(
      mockedGetModerationQueue,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.get(
        '[role="status"]',
      ).text(),
    ).toBe(
      'Resursa a fost publicată în catalogul OpenEdu.',
    )
  })

  it('afișează conflictul 409 când starea resursei s-a schimbat', async () => {
    mockedGetModerationQueue
      .mockResolvedValue([
        queueItem(),
      ])

    mockedApproveResourceVersion
      .mockRejectedValue(
        new ApiError(409, null),
      )

    const {
      wrapper,
    } = await mountModeration()

    await wrapper
      .get('.approve-button')
      .trigger('click')

    await flushPromises()

    expect(
      wrapper.get(
        '[role="alert"]',
      ).text(),
    ).toBe(
      'Starea resursei s-a modificat. Reîncarcă lista.',
    )

    expect(
      mockedGetModerationQueue,
    ).toHaveBeenCalledTimes(1)
  })

  it('afișează eroarea 403 pentru o acțiune fără permisiune', async () => {
    mockedGetModerationQueue
      .mockResolvedValue([
        queueItem(
          102,
          'approved',
        ),
      ])

    mockedPublishResourceVersion
      .mockRejectedValue(
        new ApiError(403, null),
      )

    const {
      wrapper,
    } = await mountModeration()

    await wrapper
      .get('.publish-button')
      .trigger('click')

    await flushPromises()

    expect(
      wrapper.get(
        '[role="alert"]',
      ).text(),
    ).toBe(
      'Nu ai permisiunea necesară pentru această acțiune.',
    )
  })

  it('păstrează selecția la actualizarea cozii dacă materialul mai există', async () => {
    mockedGetModerationQueue
      .mockResolvedValueOnce([
        queueItem(101),
        queueItem(102),
      ])
      .mockResolvedValueOnce([
        queueItem(101),
        queueItem(102),
        queueItem(103),
      ])

    const {
      wrapper,
    } = await mountModeration()

    const cards =
      wrapper.findAll('.queue-card')

    await cards[1]!.trigger('click')

    expect(
      wrapper
        .get('.moderation-preview')
        .text(),
    ).toContain(
      'Material 102',
    )

    await wrapper
      .get('.refresh-button')
      .trigger('click')

    await flushPromises()

    expect(
      mockedGetModerationQueue,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper
        .get('.moderation-preview')
        .text(),
    ).toContain(
      'Material 102',
    )
  })

  it('trimite utilizatorul la login dacă sesiunea a expirat', async () => {
    mockedGetModerationQueue
      .mockRejectedValue(
        new ApiError(401, null),
      )

    const {
      router,
    } = await mountModeration()

    expect(
      router.currentRoute.value.name,
    ).toBe('login')

    expect(
      router.currentRoute.value.query,
    ).toEqual({
      redirect: '/moderation',
    })
  })
})
