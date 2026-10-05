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
import ResourcesView from '../src/views/ResourcesView.vue'

import type {
  ConceptPlacement,
  ConceptResourceLink,
  CurriculumSubject,
  EducationLevel,
} from '../src/types/catalog'

vi.mock('../src/api/catalog', () => ({
  getEducationLevels: vi.fn(),
  getEducationLevelSubjects: vi.fn(),
  getCurriculumSubjectConcepts: vi.fn(),
  getConceptResources: vi.fn(),
}))

const mockedGetEducationLevels = vi.mocked(
  catalogApi.getEducationLevels,
)

const mockedGetEducationLevelSubjects = vi.mocked(
  catalogApi.getEducationLevelSubjects,
)

const mockedGetCurriculumSubjectConcepts = vi.mocked(
  catalogApi.getCurriculumSubjectConcepts,
)

const mockedGetConceptResources = vi.mocked(
  catalogApi.getConceptResources,
)

function level(
  id = 1,
): EducationLevel {
  return {
    id,
    code: `level-${id}`,
    name: `Clasa ${id}`,
    ordinal: id,
    education_stage: 'Gimnaziu',
  }
}

function subject(
  id = 10,
): CurriculumSubject {
  return {
    id,
    display_order: 1,
    subject: {
      id: id + 100,
      code: `subject-${id}`,
      name: `Matematică ${id}`,
      description: 'Descriere materie',
    },
  }
}

function concept(
  id: number,
  conceptId: number,
  domainId = 50,
): ConceptPlacement {
  return {
    id,
    display_order: id,
    is_core: true,
    domain: {
      id: domainId,
      title: `Domeniul ${domainId}`,
      description: 'Descriere domeniu',
      display_order: 1,
    },
    concept: {
      id: conceptId,
      code: `CON-${conceptId}`,
      title: `Concept ${conceptId}`,
      description: 'Descriere concept',
    },
  }
}

function linkedResource(
  id = 1,
  resourceId = 700,
): ConceptResourceLink {
  return {
    id,
    is_primary: true,
    display_order: 1,
    resource: {
      id: resourceId,
      code: `RES-${resourceId}`,
      type: 'lesson',
      version: {
        id: resourceId + 1000,
        version_number: 1,
        title: `Resursa ${resourceId}`,
        summary: 'Rezumat resursă',
        language_code: 'ro',
        difficulty_level: 3,
        complexity_level: 4,
        published_at: '2026-10-01T12:00:00Z',
      },
    },
  }
}

async function mountView() {
  const pinia = createPinia()
  setActivePinia(pinia)

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/resources',
        name: 'resources',
        component: {
          template: '<div>Resources</div>',
        },
      },
      {
        path: '/resources/:resourceId',
        name: 'resource-detail',
        component: {
          template: '<div>Resource detail</div>',
        },
      },
    ],
  })

  await router.push('/resources')
  await router.isReady()

  const wrapper = mount(ResourcesView, {
    global: {
      plugins: [
        pinia,
        router,
      ],
      stubs: {
        ResourceSearchPanel: true,
      },
    },
  })

  await flushPromises()

  return {
    wrapper,
    router,
  }
}

describe('ResourcesView', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    mockedGetEducationLevels.mockResolvedValue(
      [],
    )

    mockedGetEducationLevelSubjects.mockResolvedValue(
      [],
    )

    mockedGetCurriculumSubjectConcepts.mockResolvedValue(
      [],
    )

    mockedGetConceptResources.mockResolvedValue(
      [],
    )
  })

  it('încarcă și afișează nivelurile la montarea paginii', async () => {
    mockedGetEducationLevels.mockResolvedValue([
      level(5),
      level(6),
    ])

    const {
      wrapper,
    } = await mountView()

    expect(
      mockedGetEducationLevels,
    ).toHaveBeenCalledTimes(1)

    expect(
      wrapper.text(),
    ).toContain('Clasa 5')

    expect(
      wrapper.text(),
    ).toContain('Clasa 6')
  })

  it('parcurge fluxul nivel → materie → concept → resurse', async () => {
    const selectedLevel =
      level(5)

    const selectedSubject =
      subject(10)

    const selectedConcept =
      concept(20, 200)

    mockedGetEducationLevels.mockResolvedValue([
      selectedLevel,
    ])

    mockedGetEducationLevelSubjects.mockResolvedValue([
      selectedSubject,
    ])

    mockedGetCurriculumSubjectConcepts.mockResolvedValue([
      selectedConcept,
    ])

    mockedGetConceptResources.mockResolvedValue([
      linkedResource(1, 700),
    ])

    const {
      wrapper,
    } = await mountView()

    await wrapper
      .get('.catalog-option')
      .trigger('click')

    await flushPromises()

    expect(
      mockedGetEducationLevelSubjects,
    ).toHaveBeenCalledWith(
      selectedLevel.id,
    )

    expect(
      wrapper.text(),
    ).toContain('Matematică 10')

    const subjectButton =
      wrapper
        .findAll('.catalog-option')
        .find(
          (button) =>
            button.text().includes(
              'Matematică 10',
            ),
        )

    expect(subjectButton).toBeDefined()

    await subjectButton!.trigger('click')

    await flushPromises()

    expect(
      mockedGetCurriculumSubjectConcepts,
    ).toHaveBeenCalledWith(
      selectedSubject.id,
    )

    expect(
      wrapper.text(),
    ).toContain('Concept 200')

    await wrapper
      .get('.concept-card')
      .trigger('click')

    await flushPromises()

    expect(
      mockedGetConceptResources,
    ).toHaveBeenCalledWith(200)

    expect(
      wrapper.text(),
    ).toContain('Resursa 700')

    expect(
      wrapper.text(),
    ).toContain('1 resursă')

    const resourceLink =
      wrapper.get(
        '.catalog-resource-card',
      )

    expect(
      resourceLink.attributes('href'),
    ).toBe('/resources/700')
  })

  it('grupează conceptele care aparțin aceluiași domeniu', async () => {
    mockedGetEducationLevels.mockResolvedValue([
      level(5),
    ])

    mockedGetEducationLevelSubjects.mockResolvedValue([
      subject(10),
    ])

    mockedGetCurriculumSubjectConcepts.mockResolvedValue([
      concept(20, 200, 50),
      concept(21, 201, 50),
    ])

    const {
      wrapper,
    } = await mountView()

    await wrapper
      .get('.catalog-option')
      .trigger('click')

    await flushPromises()

    const subjectButton =
      wrapper
        .findAll('.catalog-option')
        .find(
          (button) =>
            button.text().includes(
              'Matematică 10',
            ),
        )

    await subjectButton!.trigger('click')

    await flushPromises()

    expect(
      wrapper.findAll('.concept-group'),
    ).toHaveLength(1)

    expect(
      wrapper.findAll('.concept-card'),
    ).toHaveLength(2)

    expect(
      wrapper.text(),
    ).toContain('Concept 200')

    expect(
      wrapper.text(),
    ).toContain('Concept 201')
  })

  it('afișează starea goală când conceptul nu are resurse', async () => {
    mockedGetEducationLevels.mockResolvedValue([
      level(),
    ])

    mockedGetEducationLevelSubjects.mockResolvedValue([
      subject(),
    ])

    mockedGetCurriculumSubjectConcepts.mockResolvedValue([
      concept(20, 200),
    ])

    mockedGetConceptResources.mockResolvedValue(
      [],
    )

    const {
      wrapper,
    } = await mountView()

    await wrapper
      .get('.catalog-option')
      .trigger('click')

    await flushPromises()

    const subjectButton =
      wrapper
        .findAll('.catalog-option')
        .find(
          (button) =>
            button.text().includes(
              'Matematică',
            ),
        )

    await subjectButton!.trigger('click')

    await flushPromises()

    await wrapper
      .get('.concept-card')
      .trigger('click')

    await flushPromises()

    expect(
      wrapper.text(),
    ).toContain(
      'Nu există momentan resurse publicate asociate acestui concept.',
    )

    expect(
      wrapper.text(),
    ).toContain('0 resurse')
  })

  it('permite retry după eroarea de încărcare a catalogului', async () => {
    mockedGetEducationLevels
      .mockRejectedValueOnce(
        new Error(
          'Catalog indisponibil',
        ),
      )
      .mockResolvedValueOnce([
        level(5),
      ])

    const {
      wrapper,
    } = await mountView()

    expect(
      wrapper.get(
        '.catalog-error',
      ).text(),
    ).toContain(
      'Catalog indisponibil',
    )

    await wrapper
      .get(
        '.catalog-error .catalog-retry',
      )
      .trigger('click')

    await flushPromises()

    expect(
      mockedGetEducationLevels,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.find(
        '.catalog-error',
      ).exists(),
    ).toBe(false)

    expect(
      wrapper.text(),
    ).toContain('Clasa 5')
  })

  it('permite retry după eroarea resurselor unui concept', async () => {
    mockedGetEducationLevels.mockResolvedValue([
      level(),
    ])

    mockedGetEducationLevelSubjects.mockResolvedValue([
      subject(),
    ])

    mockedGetCurriculumSubjectConcepts.mockResolvedValue([
      concept(20, 200),
    ])

    mockedGetConceptResources
      .mockRejectedValueOnce(
        new Error(
          'Resurse indisponibile',
        ),
      )
      .mockResolvedValueOnce([
        linkedResource(),
      ])

    const {
      wrapper,
    } = await mountView()

    await wrapper
      .get('.catalog-option')
      .trigger('click')

    await flushPromises()

    const subjectButton =
      wrapper
        .findAll('.catalog-option')
        .find(
          (button) =>
            button.text().includes(
              'Matematică',
            ),
        )

    await subjectButton!.trigger('click')

    await flushPromises()

    await wrapper
      .get('.concept-card')
      .trigger('click')

    await flushPromises()

    expect(
      wrapper.get(
        '.catalog-resource-error',
      ).text(),
    ).toContain(
      'Resurse indisponibile',
    )

    await wrapper
      .get(
        '.catalog-resource-error .catalog-retry',
      )
      .trigger('click')

    await flushPromises()

    expect(
      mockedGetConceptResources,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.find(
        '.catalog-resource-error',
      ).exists(),
    ).toBe(false)

    expect(
      wrapper.text(),
    ).toContain('Resursa 700')
  })
})
