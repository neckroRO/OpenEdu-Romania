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
import * as editorialApi from '../src/api/editorial'
import { ApiError } from '../src/api/client'
import TeacherView from '../src/views/TeacherView.vue'

import type {
  ConceptPlacement,
  CurriculumSubject,
  EducationLevel,
} from '../src/types/catalog'
import type {
  EditorialResource,
  EditorialResourceVersion,
  EditorialStatus,
} from '../src/types/editorial'
import type {
  EducationalResourceDetail,
} from '../src/types/resource'

vi.mock('../src/api/catalog', () => ({
  getEducationLevels: vi.fn(),
  getEducationLevelSubjects: vi.fn(),
  getCurriculumSubjectConcepts: vi.fn(),
}))

vi.mock('../src/api/editorial', () => ({
  getEditorialResources: vi.fn(),
  createEditorialResource: vi.fn(),
  updateEditorialResource: vi.fn(),
  submitResourceVersion: vi.fn(),
  reviseResourceVersion: vi.fn(),
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

const mockedGetEditorialResources = vi.mocked(
  editorialApi.getEditorialResources,
)

const mockedCreateEditorialResource = vi.mocked(
  editorialApi.createEditorialResource,
)

const mockedUpdateEditorialResource = vi.mocked(
  editorialApi.updateEditorialResource,
)

const mockedSubmitResourceVersion = vi.mocked(
  editorialApi.submitResourceVersion,
)

const mockedReviseResourceVersion = vi.mocked(
  editorialApi.reviseResourceVersion,
)

function educationLevel(
  id = 5,
): EducationLevel {
  return {
    id,
    code: `level-${id}`,
    name: `Clasa ${id}`,
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
      name: 'Matematică',
      description: 'Matematică gimnaziu',
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
      id: 50,
      title: 'Numere',
      description: 'Domeniu numeric',
      display_order: 1,
    },
    concept: {
      id: conceptId,
      code: `CON-${conceptId}`,
      title: 'Fracții',
      description: 'Concept de test',
    },
  }
}

function version(
  status: EditorialStatus = 'draft',
  id = 101,
): EditorialResourceVersion {
  return {
    id,
    resource_id: id - 100,
    version_number: 1,
    title: `Resursa ${id}`,
    summary: 'Rezumat editorial',
    content: 'Conținut editorial',
    source_url: 'https://example.com/source',
    language_code: 'ro',
    difficulty_level: 3,
    complexity_level: 4,
    status,
    submitted_at:
      status === 'submitted' ||
      status === 'rejected' ||
      status === 'approved' ||
      status === 'published'
        ? '2026-10-01T10:00:00Z'
        : null,
    reviewed_by:
      status === 'rejected' ||
      status === 'approved' ||
      status === 'published'
        ? 9
        : null,
    reviewed_at:
      status === 'rejected' ||
      status === 'approved' ||
      status === 'published'
        ? '2026-10-02T10:00:00Z'
        : null,
    review_note:
      status === 'rejected'
        ? 'Completează explicația.'
        : null,
    published_at:
      status === 'published'
        ? '2026-10-03T10:00:00Z'
        : null,
  }
}

function editorialResource(
  id = 1,
  status: EditorialStatus = 'draft',
): EditorialResource {
  return {
    id,
    code: `RES-${id}`,
    type: 'explanation',
    status: 'active',
    concept: {
      id: 200,
      code: 'CON-200',
      title: 'Fracții',
    },
    version: {
      ...version(
        status,
        id + 100,
      ),
      resource_id: id,
      title: `Material ${id}`,
    },
  }
}

function resourceDetail(
  id = 1,
): EducationalResourceDetail {
  return {
    id,
    code: `RES-${id}`,
    type: 'explanation',
    version: {
      id: id + 100,
      version_number: 1,
      title: `Material ${id}`,
      summary: 'Rezumat',
      content: 'Conținut',
      source_url: null,
      language_code: 'ro',
      difficulty_level: 1,
      complexity_level: 1,
      published_at: null,
    },
  }
}

async function mountTeacher() {
  const pinia = createPinia()
  setActivePinia(pinia)

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/teacher',
        name: 'teacher',
        component: {
          template: '<div>Teacher</div>',
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

  await router.push('/teacher')
  await router.isReady()

  const wrapper = mount(
    TeacherView,
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

describe('TeacherView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()

    Element.prototype.scrollIntoView =
      vi.fn()

    vi.stubGlobal(
      'requestAnimationFrame',
      (
        callback: FrameRequestCallback,
      ) => {
        callback(0)
        return 1
      },
    )

    mockedGetEditorialResources
      .mockResolvedValue([])

    mockedGetEducationLevels
      .mockResolvedValue([])

    mockedGetEducationLevelSubjects
      .mockResolvedValue([])

    mockedGetCurriculumSubjectConcepts
      .mockResolvedValue([])

    mockedCreateEditorialResource
      .mockResolvedValue(
        resourceDetail(),
      )

    mockedUpdateEditorialResource
      .mockResolvedValue(
        resourceDetail(),
      )

    mockedSubmitResourceVersion
      .mockResolvedValue(
        version('submitted'),
      )

    mockedReviseResourceVersion
      .mockResolvedValue(
        version('draft'),
      )
  })

  it('încarcă workspace-ul și afișează statisticile editoriale', async () => {
    mockedGetEditorialResources
      .mockResolvedValue([
        editorialResource(1, 'draft'),
        editorialResource(2, 'submitted'),
        editorialResource(3, 'rejected'),
        editorialResource(4, 'approved'),
        editorialResource(5, 'published'),
      ])

    mockedGetEducationLevels
      .mockResolvedValue([
        educationLevel(),
      ])

    const {
      wrapper,
    } = await mountTeacher()

    expect(
      mockedGetEditorialResources,
    ).toHaveBeenCalledTimes(1)

    expect(
      mockedGetEducationLevels,
    ).toHaveBeenCalledTimes(1)

    const stats =
      wrapper.findAll(
        '.teacher-stats > div',
      )

    expect(stats[0]!.text()).toContain(
      'Total',
    )
    expect(stats[0]!.text()).toContain(
      '5',
    )

    expect(stats[1]!.text()).toContain(
      'Draft',
    )
    expect(stats[1]!.text()).toContain(
      '1',
    )

    expect(stats[2]!.text()).toContain(
      'În validare',
    )
    expect(stats[2]!.text()).toContain(
      '1',
    )

    expect(stats[3]!.text()).toContain(
      'Publicate',
    )
    expect(stats[3]!.text()).toContain(
      '1',
    )
  })

  it('butonul Resursă nouă resetează editorul și derulează la formular', async () => {
    const {
      wrapper,
    } = await mountTeacher()

    const editor = wrapper
      .get('#teacher-editor')
      .element

    const querySelectorSpy = vi
      .spyOn(document, 'querySelector')
      .mockReturnValue(editor)

    const titleInput = wrapper.get(
      'input[placeholder="Titlul resursei"]',
    )

    await titleInput.setValue(
      'Titlu temporar',
    )

    await wrapper
      .get('.teacher-new-button')
      .trigger('click')

    await flushPromises()

    expect(
      (
        wrapper.get(
          'input[placeholder="Titlul resursei"]',
        ).element as HTMLInputElement
      ).value,
    ).toBe('')

    expect(
      querySelectorSpy,
    ).toHaveBeenCalledWith(
      '#teacher-editor',
    )

    expect(
      Element.prototype.scrollIntoView,
    ).toHaveBeenCalled()

    querySelectorSpy.mockRestore()
  })

  it('validează titlul înainte de crearea unui draft', async () => {
    const {
      wrapper,
    } = await mountTeacher()

    await wrapper
      .get('.teacher-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper.get(
        '.editor-error',
      ).text(),
    ).toBe(
      'Titlul și tipul resursei sunt obligatorii.',
    )

    expect(
      mockedCreateEditorialResource,
    ).not.toHaveBeenCalled()
  })

  it('validează codul limbii înainte de salvare', async () => {
    const {
      wrapper,
    } = await mountTeacher()

    await wrapper
      .get(
        'input[placeholder="Titlul resursei"]',
      )
      .setValue('Test')

    await wrapper
      .get(
        'input[placeholder="ro"]',
      )
      .setValue('ron')

    await wrapper
      .get('.teacher-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper.get(
        '.editor-error',
      ).text(),
    ).toBe(
      'Codul limbii trebuie să conțină două litere.',
    )

    expect(
      mockedCreateEditorialResource,
    ).not.toHaveBeenCalled()
  })

  it('cere concept principal la crearea unei resurse', async () => {
    const {
      wrapper,
    } = await mountTeacher()

    await wrapper
      .get(
        'input[placeholder="Titlul resursei"]',
      )
      .setValue(
        'Fracții echivalente',
      )

    await wrapper
      .get('.teacher-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper.get(
        '.editor-error',
      ).text(),
    ).toBe(
      'Selectează conceptul principal al resursei.',
    )

    expect(
      mockedCreateEditorialResource,
    ).not.toHaveBeenCalled()
  })

  it('creează un draft cu date normalizate și concept selectat', async () => {
    mockedGetEducationLevels
      .mockResolvedValue([
        educationLevel(5),
      ])

    mockedGetEducationLevelSubjects
      .mockResolvedValue([
        curriculumSubject(10),
      ])

    mockedGetCurriculumSubjectConcepts
      .mockResolvedValue([
        conceptPlacement(20, 200),
      ])

    const {
      wrapper,
    } = await mountTeacher()

    const conceptSelects =
      wrapper.findAll(
        '.teacher-concept-selector select',
      )

    await conceptSelects[0]!
      .setValue('5')

    await flushPromises()

    expect(
      mockedGetEducationLevelSubjects,
    ).toHaveBeenCalledWith(5)

    const subjectSelects =
      wrapper.findAll(
        '.teacher-concept-selector select',
      )

    await subjectSelects[1]!
      .setValue('10')

    await flushPromises()

    expect(
      mockedGetCurriculumSubjectConcepts,
    ).toHaveBeenCalledWith(10)

    const finalSelects =
      wrapper.findAll(
        '.teacher-concept-selector select',
      )

    await finalSelects[2]!
      .setValue('200')

    await wrapper
      .get(
        'input[placeholder="Titlul resursei"]',
      )
      .setValue(
        '  Fracții echivalente  ',
      )

    await wrapper
      .get(
        'input[placeholder="explanation"]',
      )
      .setValue(' lesson ')

    await wrapper
      .get(
        'textarea[placeholder="Descriere scurtă a resursei"]',
      )
      .setValue('  Rezumat nou  ')

    await wrapper
      .get(
        'textarea[placeholder="Conținutul educațional..."]',
      )
      .setValue('  Conținut nou  ')

    await wrapper
      .get(
        'input[placeholder="https://..."]',
      )
      .setValue(
        '  https://example.com/test  ',
      )

    await wrapper
      .get(
        'input[placeholder="ro"]',
      )
      .setValue('RO')

    const levels =
      wrapper.findAll(
        '.form-three select',
      )

    await levels[0]!.setValue('3')
    await levels[1]!.setValue('4')

    await wrapper
      .get('.teacher-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedCreateEditorialResource,
    ).toHaveBeenCalledWith({
      concept_id: 200,
      type: 'lesson',
      title: 'Fracții echivalente',
      summary: 'Rezumat nou',
      content: 'Conținut nou',
      source_url:
        'https://example.com/test',
      language_code: 'ro',
      difficulty_level: 3,
      complexity_level: 4,
    })

    expect(
      mockedGetEditorialResources,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.get(
        '[role="status"]',
      ).text(),
    ).toBe(
      'Resursa a fost creată și salvată ca draft.',
    )
  })

  it('încarcă un draft în editor și îl actualizează', async () => {
    const draft =
      editorialResource(
        7,
        'draft',
      )

    mockedGetEditorialResources
      .mockResolvedValue([
        draft,
      ])

    const {
      wrapper,
    } = await mountTeacher()

    const editButton =
      wrapper
        .findAll(
          '.teacher-resource-actions button',
        )
        .find(
          (button) =>
            button.text() === 'Editează',
        )

    expect(editButton).toBeDefined()

    await editButton!.trigger('click')

    expect(
      wrapper.text(),
    ).toContain(
      'Actualizează materialul',
    )

    expect(
      wrapper.text(),
    ).toContain(
      'Concept actual',
    )

    const titleInput =
      wrapper.get(
        'input[placeholder="Titlul resursei"]',
      )

    expect(
      (
        titleInput.element as
          HTMLInputElement
      ).value,
    ).toBe('Material 7')

    await titleInput.setValue(
      '  Material actualizat  ',
    )

    await wrapper
      .get('.teacher-form')
      .trigger('submit')

    await flushPromises()

    expect(
      mockedUpdateEditorialResource,
    ).toHaveBeenCalledWith(
      7,
      expect.objectContaining({
        title: 'Material actualizat',
        type: 'explanation',
        language_code: 'ro',
        difficulty_level: 3,
        complexity_level: 4,
      }),
    )

    const payload =
      mockedUpdateEditorialResource
        .mock.calls[0]![1]

    expect(payload).not.toHaveProperty(
      'concept_id',
    )

    expect(
      wrapper.get(
        '[role="status"]',
      ).text(),
    ).toBe(
      'Draftul a fost actualizat.',
    )
  })

  it('trimite un draft pentru validare', async () => {
    const draft =
      editorialResource(
        3,
        'draft',
      )

    mockedGetEditorialResources
      .mockResolvedValue([
        draft,
      ])

    const {
      wrapper,
    } = await mountTeacher()

    const submitButton =
      wrapper
        .findAll(
          '.teacher-resource-actions button',
        )
        .find(
          (button) =>
            button.text().includes(
              'Trimite la validare',
            ),
        )

    expect(submitButton).toBeDefined()

    await submitButton!.trigger('click')

    await flushPromises()

    expect(
      mockedSubmitResourceVersion,
    ).toHaveBeenCalledWith(
      draft.version!.id,
    )

    expect(
      mockedGetEditorialResources,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.get(
        '[role="status"]',
      ).text(),
    ).toBe(
      'Resursa a fost trimisă pentru validare.',
    )
  })

  it('afișează feedbackul moderatorului și readuce resursa respinsă în editor', async () => {
    const rejected =
      editorialResource(
        9,
        'rejected',
      )

    const revised: EditorialResource = {
      ...rejected,
      version: {
        ...rejected.version!,
        status: 'draft',
        review_note: null,
      },
    }

    mockedGetEditorialResources
      .mockResolvedValueOnce([
        rejected,
      ])
      .mockResolvedValueOnce([
        revised,
      ])

    const {
      wrapper,
    } = await mountTeacher()

    expect(
      wrapper.text(),
    ).toContain(
      'Feedback moderator',
    )

    expect(
      wrapper.text(),
    ).toContain(
      'Completează explicația.',
    )

    const reviseButton =
      wrapper
        .findAll(
          '.teacher-resource-actions button',
        )
        .find(
          (button) =>
            button.text().includes(
              'Revizuiește',
            ),
        )

    expect(reviseButton).toBeDefined()

    await reviseButton!.trigger('click')

    await flushPromises()

    expect(
      mockedReviseResourceVersion,
    ).toHaveBeenCalledWith(
      rejected.version!.id,
    )

    expect(
      wrapper.text(),
    ).toContain(
      'Actualizează materialul',
    )

    expect(
      wrapper.get(
        'input[placeholder="Titlul resursei"]',
      ).element,
    ).toHaveProperty(
      'value',
      'Material 9',
    )

    expect(
      wrapper.get(
        '[role="status"]',
      ).text(),
    ).toBe(
      'Resursa a revenit în starea draft și poate fi editată.',
    )
  })

  it('permite reîncărcarea workspace-ului după o eroare', async () => {
    mockedGetEditorialResources
      .mockRejectedValueOnce(
        new Error('Server indisponibil'),
      )
      .mockResolvedValueOnce([
        editorialResource(
          4,
          'draft',
        ),
      ])

    const {
      wrapper,
    } = await mountTeacher()

    expect(
      wrapper.get(
        '.teacher-error',
      ).text(),
    ).toContain(
      'Nu am putut încărca resursele tale.',
    )

    await wrapper
      .get(
        '.teacher-error button',
      )
      .trigger('click')

    await flushPromises()

    expect(
      mockedGetEditorialResources,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.find(
        '.teacher-error',
      ).exists(),
    ).toBe(false)

    expect(
      wrapper.text(),
    ).toContain(
      'Material 4',
    )
  })

  it('trimite utilizatorul la login când sesiunea a expirat', async () => {
    mockedGetEditorialResources
      .mockRejectedValue(
        new ApiError(401, null),
      )

    const {
      router,
    } = await mountTeacher()

    expect(
      router.currentRoute.value.name,
    ).toBe('login')

    expect(
      router.currentRoute.value.query,
    ).toEqual({
      redirect: '/teacher',
    })
  })

  it('afișează mesajul de permisiune pentru eroarea 403 la salvare', async () => {
    const draft =
      editorialResource(
        11,
        'draft',
      )

    mockedGetEditorialResources
      .mockResolvedValue([
        draft,
      ])

    mockedUpdateEditorialResource
      .mockRejectedValue(
        new ApiError(403, null),
      )

    const {
      wrapper,
    } = await mountTeacher()

    await wrapper
      .get(
        '.teacher-resource-actions .secondary-action',
      )
      .trigger('click')

    await wrapper
      .get('.teacher-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper.get(
        '.editor-error',
      ).text(),
    ).toBe(
      'Nu ai permisiunea necesară pentru această acțiune.',
    )
  })

  it('afișează mesajul de validare pentru eroarea 422 la salvare', async () => {
    const draft =
      editorialResource(
        12,
        'draft',
      )

    mockedGetEditorialResources
      .mockResolvedValue([
        draft,
      ])

    mockedUpdateEditorialResource
      .mockRejectedValue(
        new ApiError(422, null),
      )

    const {
      wrapper,
    } = await mountTeacher()

    await wrapper
      .get(
        '.teacher-resource-actions .secondary-action',
      )
      .trigger('click')

    await wrapper
      .get('.teacher-form')
      .trigger('submit')

    await flushPromises()

    expect(
      wrapper.get(
        '.editor-error',
      ).text(),
    ).toBe(
      'Verifică datele completate în formular.',
    )
  })

  it('afișează conflictul 409 dacă draftul nu mai poate fi trimis', async () => {
    const draft =
      editorialResource(
        13,
        'draft',
      )

    mockedGetEditorialResources
      .mockResolvedValue([
        draft,
      ])

    mockedSubmitResourceVersion
      .mockRejectedValue(
        new ApiError(409, null),
      )

    const {
      wrapper,
    } = await mountTeacher()

    const submitButton =
      wrapper
        .findAll(
          '.teacher-resource-actions button',
        )
        .find(
          (button) =>
            button.text().includes(
              'Trimite la validare',
            ),
        )

    expect(submitButton).toBeDefined()

    await submitButton!.trigger('click')

    await flushPromises()

    expect(
      wrapper.get(
        '.teacher-error',
      ).text(),
    ).toContain(
      'Acțiunea nu mai este disponibilă în starea curentă.',
    )

    expect(
      mockedGetEditorialResources,
    ).toHaveBeenCalledTimes(1)
  })

})
