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
  entityType:
    CurriculumProposal['entity_type'] = 'domain',
): CurriculumProposal {
  return {
    id,
    entity_type: entityType,
    entity_id:
      entityType === 'subject'
        ? null
        : id,
    proposal_type: 'create',
    payload:
      entityType === 'concept'
        ? {
            code: `CON-${id}`,
            title: `Concept ${id}`,
          }
        : entityType === 'domain'
          ? {
              title: `Domeniu ${id}`,
              curriculum_subject_id: 4,
            }
          : {
              name: `Materia ${id}`,
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
        ? 'Necesită corecturi.'
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

    expect(stats[0]!.text()).toContain('5')
    expect(stats[1]!.text()).toContain('2')
    expect(stats[2]!.text()).toContain('1')
    expect(stats[3]!.text()).toContain('2')
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
      'Necesită corecturi.',
    )
  })

  it('creează un domeniu', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="curriculum-subject-id"]')
      .setValue('4')

    await wrapper
      .get('[data-testid="title"]')
      .setValue('  Algebră  ')

    await wrapper
      .get('[data-testid="description"]')
      .setValue(
        '  Structuri algebrice  ',
      )

    await wrapper
      .get('[data-testid="display-order"]')
      .setValue('10')

    await wrapper
      .get('[data-testid="reason"]')
      .setValue(
        '  Organizare curriculară.  ',
      )

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'domain',
      proposal_type: 'create',
      payload: {
        curriculum_subject_id: 4,
        title: 'Algebră',
        description:
          'Structuri algebrice',
        display_order: 10,
      },
      reason:
        'Organizare curriculară.',
    })
  })

  it('actualizează un domeniu', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('update')

    await wrapper
      .get('[data-testid="entity-id"]')
      .setValue('12')

    await wrapper
      .get('[data-testid="title"]')
      .setValue(
        'Algebră și ecuații',
      )

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'domain',
      entity_id: 12,
      proposal_type: 'update',
      payload: {
        title: 'Algebră și ecuații',
      },
      reason: null,
    })
  })

  it('creează un concept', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await wrapper
      .get('[data-testid="code"]')
      .setValue('  ALG-EQ1  ')

    await wrapper
      .get('[data-testid="title"]')
      .setValue(
        '  Ecuații de gradul I  ',
      )

    await wrapper
      .get('[data-testid="description"]')
      .setValue(
        '  Introducere în ecuații.  ',
      )

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'concept',
      proposal_type: 'create',
      payload: {
        code: 'ALG-EQ1',
        title: 'Ecuații de gradul I',
        description:
          'Introducere în ecuații.',
      },
      reason: null,
    })
  })

  it('propune un alias pentru concept', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('alias')

    await wrapper
      .get('[data-testid="entity-id"]')
      .setValue('22')

    await wrapper
      .get('[data-testid="alias"]')
      .setValue(
        '  Ecuații liniare  ',
      )

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'concept',
      entity_id: 22,
      proposal_type: 'alias',
      payload: {
        alias: 'Ecuații liniare',
      },
      reason: null,
    })
  })

  it('propune un concept duplicat', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('merge_candidate')

    await wrapper
      .get('[data-testid="entity-id"]')
      .setValue('30')

    await wrapper
      .get('[data-testid="duplicate-entity-id"]')
      .setValue('8')

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'concept',
      entity_id: 30,
      proposal_type:
        'merge_candidate',
      payload: {
        duplicate_entity_id: 8,
      },
      reason: null,
    })
  })

  it('refuză merge-ul conceptului în el însuși', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('merge_candidate')

    await wrapper
      .get('[data-testid="entity-id"]')
      .setValue('30')

    await wrapper
      .get('[data-testid="duplicate-entity-id"]')
      .setValue('30')

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper
        .get('.curriculum-form-error')
        .text(),
    ).toContain(
      'trebuie să fie diferite',
    )

    expect(
      mockedCreateCurriculumProposal,
    ).not.toHaveBeenCalled()
  })

  it('validează câmpurile obligatorii pentru domeniu', async () => {
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
      'ID-ul disciplinei curriculare este obligatoriu.',
    )

    expect(
      mockedCreateCurriculumProposal,
    ).not.toHaveBeenCalled()
  })
})
