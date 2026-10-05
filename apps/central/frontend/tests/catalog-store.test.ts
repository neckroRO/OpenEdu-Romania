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

import * as catalogApi from '../src/api/catalog'
import { useCatalogStore } from '../src/stores/catalog'

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

function educationLevel(
  id = 1,
): EducationLevel {
  return {
    id,
    code: `level-${id}`,
    name: `Nivel ${id}`,
    ordinal: id,
    education_stage: 'Gimnaziu',
  }
}

function curriculumSubject(
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

function conceptPlacement(
  id = 20,
  conceptId = 200,
): ConceptPlacement {
  return {
    id,
    display_order: 1,
    is_core: true,
    domain: {
      id: 300,
      title: 'Numere',
      description: 'Domeniu de test',
      display_order: 1,
    },
    concept: {
      id: conceptId,
      code: `concept-${conceptId}`,
      title: `Concept ${conceptId}`,
      description: 'Descriere concept',
    },
  }
}

function conceptResource(
  id = 30,
  resourceId = 300,
): ConceptResourceLink {
  return {
    id,
    is_primary: true,
    display_order: 1,
    resource: {
      id: resourceId,
      code: `resource-${resourceId}`,
      type: 'lesson',
      version: {
        id: resourceId + 1000,
        version_number: 1,
        title: `Resursa ${resourceId}`,
        summary: 'Rezumat',
        language_code: 'ro',
        difficulty_level: 3,
        complexity_level: 4,
        published_at: '2026-10-01T12:00:00Z',
      },
    },
  }
}

function deferred<T>() {
  let resolve!: (value: T) => void
  let reject!: (reason?: unknown) => void

  const promise = new Promise<T>(
    (promiseResolve, promiseReject) => {
      resolve = promiseResolve
      reject = promiseReject
    },
  )

  return {
    promise,
    resolve,
    reject,
  }
}

describe('catalog store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('încarcă nivelurile educaționale', async () => {
    const levels = [
      educationLevel(1),
      educationLevel(2),
    ]

    mockedGetEducationLevels.mockResolvedValue(
      levels,
    )

    const store = useCatalogStore()

    await store.loadEducationLevels()

    expect(
      mockedGetEducationLevels,
    ).toHaveBeenCalledTimes(1)

    expect(store.educationLevels).toEqual(
      levels,
    )

    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('curăță navigarea dacă încărcarea nivelurilor eșuează', async () => {
    const store = useCatalogStore()

    store.selectedEducationLevel =
      educationLevel()

    store.curriculumSubjects = [
      curriculumSubject(),
    ]

    mockedGetEducationLevels.mockRejectedValue(
      new Error('Catalog indisponibil'),
    )

    await store.loadEducationLevels()

    expect(store.educationLevels).toEqual([])
    expect(
      store.selectedEducationLevel,
    ).toBeNull()

    expect(store.curriculumSubjects).toEqual(
      [],
    )

    expect(store.error).toBe(
      'Catalog indisponibil',
    )

    expect(store.loading).toBe(false)
  })

  it('selectează nivelul și încarcă materiile', async () => {
    const level = educationLevel()
    const subjects = [
      curriculumSubject(10),
      curriculumSubject(11),
    ]

    mockedGetEducationLevelSubjects.mockResolvedValue(
      subjects,
    )

    const store = useCatalogStore()

    await store.selectEducationLevel(level)

    expect(
      mockedGetEducationLevelSubjects,
    ).toHaveBeenCalledWith(level.id)

    expect(
      store.selectedEducationLevel,
    ).toEqual(level)

    expect(store.curriculumSubjects).toEqual(
      subjects,
    )

    expect(
      store.selectedCurriculumSubject,
    ).toBeNull()

    expect(store.conceptPlacements).toEqual(
      [],
    )

    expect(store.conceptResources).toEqual(
      [],
    )
  })

  it('păstrează nivelul selectat și afișează eroarea dacă materiile nu pot fi încărcate', async () => {
    const level = educationLevel()

    mockedGetEducationLevelSubjects.mockRejectedValue(
      new Error('Materii indisponibile'),
    )

    const store = useCatalogStore()

    await store.selectEducationLevel(level)

    expect(
      store.selectedEducationLevel,
    ).toEqual(level)

    expect(store.curriculumSubjects).toEqual(
      [],
    )

    expect(store.error).toBe(
      'Materii indisponibile',
    )

    expect(store.loading).toBe(false)
  })

  it('selectează materia și încarcă conceptele', async () => {
    const subject = curriculumSubject()
    const concepts = [
      conceptPlacement(20, 200),
      conceptPlacement(21, 201),
    ]

    mockedGetCurriculumSubjectConcepts
      .mockResolvedValue(concepts)

    const store = useCatalogStore()

    await store.selectCurriculumSubject(
      subject,
    )

    expect(
      mockedGetCurriculumSubjectConcepts,
    ).toHaveBeenCalledWith(subject.id)

    expect(
      store.selectedCurriculumSubject,
    ).toEqual(subject)

    expect(store.conceptPlacements).toEqual(
      concepts,
    )

    expect(
      store.selectedConceptPlacement,
    ).toBeNull()

    expect(store.conceptResources).toEqual(
      [],
    )
  })

  it('selectează conceptul și încarcă resursele asociate', async () => {
    const placement =
      conceptPlacement()

    const resources = [
      conceptResource(30, 300),
      conceptResource(31, 301),
    ]

    mockedGetConceptResources.mockResolvedValue(
      resources,
    )

    const store = useCatalogStore()

    await store.selectConcept(placement)

    expect(
      mockedGetConceptResources,
    ).toHaveBeenCalledWith(
      placement.concept.id,
    )

    expect(
      store.selectedConceptPlacement,
    ).toEqual(placement)

    expect(store.conceptResources).toEqual(
      resources,
    )

    expect(store.resourcesLoading).toBe(
      false,
    )

    expect(store.resourcesError).toBeNull()
  })

  it('afișează eroarea resurselor fără să piardă conceptul selectat', async () => {
    const placement =
      conceptPlacement()

    mockedGetConceptResources.mockRejectedValue(
      new Error('Resurse indisponibile'),
    )

    const store = useCatalogStore()

    await store.selectConcept(placement)

    expect(
      store.selectedConceptPlacement,
    ).toEqual(placement)

    expect(store.conceptResources).toEqual(
      [],
    )

    expect(store.resourcesError).toBe(
      'Resurse indisponibile',
    )

    expect(store.resourcesLoading).toBe(
      false,
    )
  })

  it('ignoră răspunsul vechi dacă utilizatorul schimbă rapid conceptul', async () => {
    const firstPlacement =
      conceptPlacement(20, 200)

    const secondPlacement =
      conceptPlacement(21, 201)

    const firstResources = [
      conceptResource(30, 300),
    ]

    const secondResources = [
      conceptResource(31, 301),
    ]

    const firstRequest =
      deferred<ConceptResourceLink[]>()

    const secondRequest =
      deferred<ConceptResourceLink[]>()

    mockedGetConceptResources
      .mockReturnValueOnce(
        firstRequest.promise,
      )
      .mockReturnValueOnce(
        secondRequest.promise,
      )

    const store = useCatalogStore()

    const firstSelection =
      store.selectConcept(firstPlacement)

    const secondSelection =
      store.selectConcept(secondPlacement)

    secondRequest.resolve(secondResources)

    await secondSelection

    expect(
      store.selectedConceptPlacement,
    ).toEqual(secondPlacement)

    expect(store.conceptResources).toEqual(
      secondResources,
    )

    firstRequest.resolve(firstResources)

    await firstSelection

    expect(
      store.selectedConceptPlacement,
    ).toEqual(secondPlacement)

    expect(store.conceptResources).toEqual(
      secondResources,
    )
  })

  it('resetarea nivelului invalidează și o cerere de resurse aflată în desfășurare', async () => {
    const placement =
      conceptPlacement()

    const request =
      deferred<ConceptResourceLink[]>()

    mockedGetConceptResources.mockReturnValue(
      request.promise,
    )

    const store = useCatalogStore()

    const selection =
      store.selectConcept(placement)

    store.clearNavigation()

    request.resolve([
      conceptResource(),
    ])

    await selection

    expect(
      store.selectedEducationLevel,
    ).toBeNull()

    expect(
      store.selectedCurriculumSubject,
    ).toBeNull()

    expect(
      store.selectedConceptPlacement,
    ).toBeNull()

    expect(store.conceptResources).toEqual(
      [],
    )

    expect(store.resourcesLoading).toBe(
      false,
    )
  })

  it('clearNavigation golește selecțiile și erorile', () => {
    const store = useCatalogStore()

    store.selectedEducationLevel =
      educationLevel()

    store.curriculumSubjects = [
      curriculumSubject(),
    ]

    store.selectedCurriculumSubject =
      curriculumSubject()

    store.conceptPlacements = [
      conceptPlacement(),
    ]

    store.selectedConceptPlacement =
      conceptPlacement()

    store.conceptResources = [
      conceptResource(),
    ]

    store.error = 'eroare'
    store.resourcesError = 'eroare resurse'

    store.clearNavigation()

    expect(
      store.selectedEducationLevel,
    ).toBeNull()

    expect(store.curriculumSubjects).toEqual(
      [],
    )

    expect(
      store.selectedCurriculumSubject,
    ).toBeNull()

    expect(store.conceptPlacements).toEqual(
      [],
    )

    expect(
      store.selectedConceptPlacement,
    ).toBeNull()

    expect(store.conceptResources).toEqual(
      [],
    )

    expect(store.error).toBeNull()
    expect(store.resourcesError).toBeNull()
  })
})
