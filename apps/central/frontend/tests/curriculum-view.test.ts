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

import * as catalogApi from '../src/api/catalog'
import * as curriculumApi from '../src/api/curriculum'
import CurriculumView from '../src/views/CurriculumView.vue'

import type {
  CurriculumProposal,
} from '../src/types/curriculum'

vi.mock('../src/api/curriculum', () => ({
  getCurriculumProposals: vi.fn(),
  createCurriculumProposal: vi.fn(),
}))

vi.mock('../src/api/catalog', () => ({
  getEducationLevels: vi.fn(),
  getEducationLevelSubjects: vi.fn(),
  getCurriculumSubjectDomains: vi.fn(),
  getCurriculumSubjectConcepts: vi.fn(),
}))

const mockedGetCurriculumProposals =
  vi.mocked(
    curriculumApi.getCurriculumProposals,
  )

const mockedCreateCurriculumProposal =
  vi.mocked(
    curriculumApi.createCurriculumProposal,
  )

const mockedGetEducationLevels =
  vi.mocked(
    catalogApi.getEducationLevels,
  )

const mockedGetEducationLevelSubjects =
  vi.mocked(
    catalogApi.getEducationLevelSubjects,
  )

const mockedGetCurriculumSubjectDomains =
  vi.mocked(
    catalogApi.getCurriculumSubjectDomains,
  )

const mockedGetCurriculumSubjectConcepts =
  vi.mocked(
    catalogApi.getCurriculumSubjectConcepts,
  )

const educationLevels = [
  {
    id: 7,
    code: 'grade-7',
    name: 'Clasa a VII-a',
    ordinal: 7,
    education_stage: 'gimnazial',
  },
] as any

const curriculumSubjects = [
  {
    id: 4,
    display_order: 1,
    subject: {
      id: 3,
      code: 'mathematics',
      name: 'Matematică',
      status: 'active',
    },
  },
] as any

const domains = [
  {
    id: 12,
    title: 'Algebră',
    description: null,
    display_order: 1,
  },
  {
    id: 13,
    title: 'Geometrie',
    description: null,
    display_order: 2,
  },
] as any

const conceptPlacements = [
  {
    id: 1,
    display_order: 1,
    is_core: true,
    domain: domains[0],
    concept: {
      id: 22,
      code: 'linear-equations',
      title: 'Ecuații de gradul I',
      description: null,
      status: 'active',
    },
  },
  {
    id: 2,
    display_order: 2,
    is_core: true,
    domain: domains[0],
    concept: {
      id: 30,
      code: 'linear-equations-alt',
      title: 'Ecuații liniare',
      description: null,
      status: 'active',
    },
  },
  {
    id: 3,
    display_order: 1,
    is_core: true,
    domain: domains[1],
    concept: {
      id: 8,
      code: 'geometry-basics',
      title: 'Elemente de geometrie',
      description: null,
      status: 'active',
    },
  },
] as any

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
            title: `Noțiune ${id}`,
            domain_id: 12,
          }
        : entityType === 'domain'
          ? {
              title: `Capitol ${id}`,
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

async function selectCatalog(
  wrapper: Awaited<
    ReturnType<typeof mountCurriculum>
  >['wrapper'],
): Promise<void> {
  await wrapper
    .get('[data-testid="education-level"]')
    .setValue('7')

  await flushPromises()

  await wrapper
    .get('[data-testid="curriculum-subject"]')
    .setValue('4')

  await flushPromises()
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

    mockedGetEducationLevels
      .mockResolvedValue(
        educationLevels,
      )

    mockedGetEducationLevelSubjects
      .mockResolvedValue(
        curriculumSubjects,
      )

    mockedGetCurriculumSubjectDomains
      .mockResolvedValue(
        domains,
      )

    mockedGetCurriculumSubjectConcepts
      .mockResolvedValue(
        conceptPlacements,
      )
  })

  it('încarcă propunerile, statisticile și nivelurile educaționale', async () => {
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

    expect(
      mockedGetEducationLevels,
    ).toHaveBeenCalledTimes(1)

    const stats = wrapper.findAll(
      '.curriculum-stats > div',
    )

    expect(stats[0]!.text()).toContain('5')
    expect(stats[1]!.text()).toContain('2')
    expect(stats[2]!.text()).toContain('1')
    expect(stats[3]!.text()).toContain('2')

    expect(
      wrapper
        .get('[data-testid="education-level"]')
        .text(),
    ).toContain('Clasa a VII-a')
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

  it('creează un capitol folosind nivelul și materia', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await selectCatalog(wrapper)

    await wrapper
      .get('[data-testid="title"]')
      .setValue('  Algebră avansată  ')

    await wrapper
      .get('[data-testid="description"]')
      .setValue(
        '  Structuri algebrice.  ',
      )

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
      mockedGetEducationLevelSubjects,
    ).toHaveBeenCalledWith(7)

    expect(
      mockedGetCurriculumSubjectDomains,
    ).toHaveBeenCalledWith(4)

    expect(
      mockedCreateCurriculumProposal,
    ).toHaveBeenCalledWith({
      entity_type: 'domain',
      proposal_type: 'create',
      payload: {
        curriculum_subject_id: 4,
        title: 'Algebră avansată',
        description:
          'Structuri algebrice.',
      },
      reason:
        'Organizare curriculară.',
    })
  })

  it('actualizează un capitol selectat după nume', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('update')

    await selectCatalog(wrapper)

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

  it('creează o noțiune în capitolul selectat și generează codul intern', async () => {
    const dateSpy = vi
      .spyOn(Date, 'now')
      .mockReturnValue(1_700_000_000_000)

    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await selectCatalog(wrapper)

    expect(
      wrapper.find(
        '[data-testid="code"]',
      ).exists(),
    ).toBe(false)

    await wrapper
      .get('[data-testid="domain-id"]')
      .setValue('12')

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
        code:
          'ecuatii-de-gradul-i-loyw3v28',
        title: 'Ecuații de gradul I',
        domain_id: 12,
        description:
          'Introducere în ecuații.',
      },
      reason: null,
    })

    dateSpy.mockRestore()
  })

  it('propune o denumire alternativă pentru noțiunea selectată', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('alias')

    await selectCatalog(wrapper)

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

  it('propune două noțiuni ca duplicate', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('merge_candidate')

    await selectCatalog(wrapper)

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

  it('nu oferă ca țintă de merge aceeași noțiune selectată ca duplicat', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await wrapper
      .get('[data-testid="proposal-type"]')
      .setValue('merge_candidate')

    await selectCatalog(wrapper)

    await wrapper
      .get('[data-testid="entity-id"]')
      .setValue('30')

    const canonicalSelect =
      wrapper.get(
        '[data-testid="duplicate-entity-id"]',
      )

    expect(
      canonicalSelect
        .find('option[value="30"]')
        .exists(),
    ).toBe(false)

    expect(
      canonicalSelect
        .find('option[value="22"]')
        .exists(),
    ).toBe(true)

    expect(
      canonicalSelect
        .find('option[value="8"]')
        .exists(),
    ).toBe(true)
  })

  it('cere contextul curricular înainte de trimitere', async () => {
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
      'Selectează nivelul educațional și materia.',
    )

    expect(
      mockedCreateCurriculumProposal,
    ).not.toHaveBeenCalled()
  })

  it('cere capitolul pentru o noțiune nouă', async () => {
    const {
      wrapper,
    } = await mountCurriculum()

    await wrapper
      .get('[data-testid="entity-type"]')
      .setValue('concept')

    await selectCatalog(wrapper)

    await wrapper
      .get('[data-testid="title"]')
      .setValue('Ecuații de gradul I')

    await wrapper
      .get('.curriculum-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper
        .get('.curriculum-form-error')
        .text(),
    ).toBe(
      'Selectează capitolul în care se încadrează noțiunea.',
    )

    expect(
      mockedCreateCurriculumProposal,
    ).not.toHaveBeenCalled()
  })
})
