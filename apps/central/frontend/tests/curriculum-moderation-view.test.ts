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

import * as curriculumApi from '../src/api/curriculum'
import CurriculumModerationView from '../src/views/CurriculumModerationView.vue'

import type {
  CurriculumProposal,
  CurriculumProposalStatus,
  CurriculumProposalType,
} from '../src/types/curriculum'

vi.mock('../src/api/curriculum', () => ({
  getCurriculumProposals: vi.fn(),
  rejectCurriculumProposal: vi.fn(),
}))

const mockedGetCurriculumProposals =
  vi.mocked(
    curriculumApi.getCurriculumProposals,
  )

const mockedRejectCurriculumProposal =
  vi.mocked(
    curriculumApi.rejectCurriculumProposal,
  )

function proposal(
  id: number,
  type: CurriculumProposalType = 'create',
  status: CurriculumProposalStatus = 'pending',
): CurriculumProposal {
  return {
    id,
    entity_type: 'subject',
    entity_id:
      type === 'create'
        ? null
        : id + 100,
    proposal_type: type,
    payload:
      type === 'merge_candidate'
        ? {
            duplicate_entity_id: id + 200,
          }
        : {
            name: `Materia ${id}`,
          },
    reason: `Motiv ${id}`,
    status,
    proposed_by: 10 + id,
    reviewed_by:
      status === 'pending'
        ? null
        : 2,
    reviewed_at:
      status === 'pending'
        ? null
        : '2026-10-08T15:00:00Z',
    review_note:
      status === 'rejected'
        ? 'Propunerea nu este potrivită.'
        : null,
    created_at:
      '2026-10-08T14:00:00Z',
    updated_at:
      '2026-10-08T15:00:00Z',
  }
}

async function mountModeration() {
  const pinia = createPinia()
  setActivePinia(pinia)

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/curriculum/moderation',
        name: 'curriculum-moderation',
        component: {
          template: '<div>Curriculum moderation</div>',
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

  await router.push(
    '/curriculum/moderation',
  )

  await router.isReady()

  const wrapper = mount(
    CurriculumModerationView,
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

describe('CurriculumModerationView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()

    mockedGetCurriculumProposals
      .mockResolvedValue([])

    mockedRejectCurriculumProposal
      .mockResolvedValue(
        proposal(
          1,
          'create',
          'rejected',
        ),
      )
  })

  it('încarcă propunerile și afișează statisticile', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValue([
        proposal(1, 'create', 'pending'),
        proposal(2, 'update', 'pending'),
        proposal(3, 'alias', 'rejected'),
        proposal(
          4,
          'merge_candidate',
          'merged',
        ),
      ])

    const {
      wrapper,
    } = await mountModeration()

    expect(
      mockedGetCurriculumProposals,
    ).toHaveBeenCalledTimes(1)

    const stats = wrapper.findAll(
      '.moderation-stats > div',
    )

    expect(stats[0]!.text()).toContain(
      '4',
    )

    expect(stats[1]!.text()).toContain(
      '2',
    )

    expect(stats[2]!.text()).toContain(
      '1',
    )

    expect(stats[3]!.text()).toContain(
      '1',
    )
  })

  it('selectează prima propunere din listă', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValue([
        proposal(5),
        proposal(6),
      ])

    const {
      wrapper,
    } = await mountModeration()

    expect(
      wrapper
        .get('.preview-code')
        .text(),
    ).toContain('#5')
  })

  it('filtrează după status și tip', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValue([
        proposal(
          1,
          'create',
          'pending',
        ),
        proposal(
          2,
          'merge_candidate',
          'pending',
        ),
        proposal(
          3,
          'merge_candidate',
          'merged',
        ),
      ])

    const {
      wrapper,
    } = await mountModeration()

    const selects =
      wrapper.findAll(
        '.filters select',
      )

    await selects[0]!
      .setValue('pending')

    await selects[1]!
      .setValue('merge_candidate')

    await flushPromises()

    const cards = wrapper.findAll(
      '.proposal-card',
    )

    expect(cards).toHaveLength(1)

    expect(
      cards[0]!.text(),
    ).toContain(
      'Posibil duplicat',
    )
  })

  it('respinge propunerea selectată', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValueOnce([
        proposal(7),
      ])
      .mockResolvedValueOnce([
        proposal(
          7,
          'create',
          'rejected',
        ),
      ])

    const {
      wrapper,
    } = await mountModeration()

    await wrapper
      .get(
        'textarea[placeholder="Motivul respingerii sau observații..."]',
      )
      .setValue(
        '  Nu corespunde structurii curriculare.  ',
      )

    await wrapper
      .get('.reject-button')
      .trigger('click')

    await flushPromises()

    expect(
      mockedRejectCurriculumProposal,
    ).toHaveBeenCalledWith(
      7,
      {
        note:
          'Nu corespunde structurii curriculare.',
      },
    )

    expect(
      mockedGetCurriculumProposals,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper
        .get('.moderation-notice')
        .text(),
    ).toBe(
      'Propunerea a fost respinsă.',
    )
  })

  it('afișează butonul de preview doar pentru merge candidate', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValue([
        proposal(
          9,
          'merge_candidate',
          'pending',
        ),
      ])

    const {
      wrapper,
    } = await mountModeration()

    expect(
      wrapper.find(
        '.preview-button',
      ).exists(),
    ).toBe(true)
  })

  it('setează query-ul merge când se deschide preview-ul', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValue([
        proposal(
          12,
          'merge_candidate',
          'pending',
        ),
      ])

    const {
      wrapper,
      router,
    } = await mountModeration()

    await wrapper
      .get('.preview-button')
      .trigger('click')

    await flushPromises()

    expect(
      router.currentRoute.value.name,
    ).toBe(
      'curriculum-moderation',
    )

    expect(
      router.currentRoute.value.query,
    ).toEqual({
      merge: '12',
    })
  })
})
