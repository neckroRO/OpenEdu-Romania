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
import CurriculumView from '../src/views/CurriculumView.vue'

import type {
  CurriculumProposal,
} from '../src/types/curriculum'

vi.mock('../src/api/curriculum', () => ({
  getCurriculumProposals: vi.fn(),
  createCurriculumProposal: vi.fn(),
}))

const mockedGetCurriculumProposals =
  vi.mocked(
    curriculumApi.getCurriculumProposals,
  )

const mockedCreateCurriculumProposal =
  vi.mocked(
    curriculumApi.createCurriculumProposal,
  )

function proposal(
  id: number,
  status: CurriculumProposal['status'] = 'pending',
): CurriculumProposal {
  return {
    id,
    entity_type: 'subject',
    entity_id: null,
    proposal_type: 'create',
    payload: {
      name: `Materia ${id}`,
      code: `MAT-${id}`,
    },
    reason: `Motiv ${id}`,
    status,
    proposed_by: 10,
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
        ? 'Materia există deja.'
        : null,
    created_at:
      '2026-10-08T14:00:00Z',
    updated_at:
      '2026-10-08T15:00:00Z',
  }
}

async function mountCurriculum() {
  const pinia = createPinia()
  setActivePinia(pinia)

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/curriculum',
        name: 'curriculum',
        component: {
          template: '<div>Curriculum</div>',
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

  await router.push('/curriculum')
  await router.isReady()

  const wrapper = mount(
    CurriculumView,
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

describe('CurriculumView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()

    mockedGetCurriculumProposals
      .mockResolvedValue([])

    mockedCreateCurriculumProposal
      .mockResolvedValue(
        proposal(10),
      )
  })

  it('încarcă propunerile și afișează statisticile', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValue([
        proposal(1, 'pending'),
        proposal(2, 'pending'),
        proposal(3, 'rejected'),
        proposal(4, 'approved'),
        proposal(5, 'merged'),
      ])

    const {
      wrapper,
    } = await mountCurriculum()

    expect(
      mockedGetCurriculumProposals,
    ).toHaveBeenCalledTimes(1)

    const stats = wrapper.findAll(
      '.curriculum-stats > div',
    )

    expect(stats[0]!.text()).toContain(
      'Total',
    )
    expect(stats[0]!.text()).toContain(
      '5',
    )

    expect(stats[1]!.text()).toContain(
      'În așteptare',
    )
    expect(stats[1]!.text()).toContain(
      '2',
    )

    expect(stats[2]!.text()).toContain(
      'Respinse',
    )
    expect(stats[2]!.text()).toContain(
      '1',
    )

    expect(stats[3]!.text()).toContain(
      'Acceptate',
    )
    expect(stats[3]!.text()).toContain(
      '2',
    )
  })

  it('afișează feedbackul moderatorului', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValue([
        proposal(3, 'rejected'),
      ])

    const {
      wrapper,
    } = await mountCurriculum()

    expect(
      wrapper
        .get('.proposal-review-note')
        .text(),
    ).toContain(
      'Materia există deja.',
    )
  })

  it('validează numele materiei', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper
        .get('.curriculum-form-error')
        .text(),
    ).toBe(
      'Numele materiei este obligatoriu.',
    )

    expect(
      mockedCreateCurriculumProposal,
    ).not.toHaveBeenCalled()
  })

  it('trimite o propunere normalizată', async () => {
    mockedGetCurriculumProposals
      .mockResolvedValueOnce([])
      .mockResolvedValueOnce([
        proposal(10),
      ])

    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get(
        'input[placeholder="Ex. Educație media"]',
      )
      .setValue(
        '  Educație media  ',
      )

    await wrapper
      .get(
        'input[placeholder="Ex. EDU-MEDIA"]',
      )
      .setValue(
        '  EDU-MEDIA  ',
      )

    await wrapper
      .get(
        'textarea[placeholder="Descrie pe scurt materia propusă..."]',
      )
      .setValue(
        '  Alfabetizare media  ',
      )

    await wrapper
      .get(
        'textarea[placeholder="De ce ar trebui introdusă această materie?"]',
      )
      .setValue(
        '  Este necesară pentru elevi.  ',
      )

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'subject',
      proposal_type: 'create',
      payload: {
        name: 'Educație media',
        code: 'EDU-MEDIA',
        description:
          'Alfabetizare media',
      },
      reason:
        'Este necesară pentru elevi.',
    })

    expect(
      mockedGetCurriculumProposals,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper
        .get('.curriculum-notice')
        .text(),
    ).toBe(
      'Propunerea a fost trimisă pentru moderare.',
    )
  })

  it('omite câmpurile opționale goale', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get(
        'input[placeholder="Ex. Educație media"]',
      )
      .setValue('Etică digitală')

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'subject',
      proposal_type: 'create',
      payload: {
        name: 'Etică digitală',
      },
      reason: null,
    })
  })
})
