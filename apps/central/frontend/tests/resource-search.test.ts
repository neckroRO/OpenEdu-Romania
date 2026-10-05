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
  createMemoryHistory,
  createRouter,
} from 'vue-router'

import * as resourcesApi from '../src/api/resources'
import ResourceSearchPanel from '../src/components/resources/ResourceSearchPanel.vue'

import type {
  EducationalResourceDetail,
  ResourceListResult,
} from '../src/types/resource'

vi.mock('../src/api/resources', () => ({
  getResource: vi.fn(),
  getResources: vi.fn(),
}))

const mockedGetResources = vi.mocked(
  resourcesApi.getResources,
)

function resource(
  id = 1,
): EducationalResourceDetail {
  return {
    id,
    code: `RES-${id}`,
    type: 'lesson',
    version: {
      id: id + 100,
      version_number: 1,
      title: `Resursa ${id}`,
      summary: `Rezumat ${id}`,
      language_code: 'ro',
      difficulty_level: 3,
      complexity_level: 4,
      published_at: '2026-10-01T12:00:00Z',
      content: `Conținut ${id}`,
      source_url: null,
    },
  }
}

function result(
  resources: EducationalResourceDetail[] = [],
  currentPage = 1,
  lastPage = 1,
  total = resources.length,
): ResourceListResult {
  return {
    data: resources,
    pagination: {
      current_page: currentPage,
      per_page: 20,
      total,
      last_page: lastPage,
      from:
        resources.length > 0
          ? (currentPage - 1) * 20 + 1
          : null,
      to:
        resources.length > 0
          ? (currentPage - 1) * 20 +
            resources.length
          : null,
    },
  }
}

async function mountSearch(
  initialPath = '/resources',
) {
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

  await router.push(initialPath)
  await router.isReady()

  const wrapper = mount(
    ResourceSearchPanel,
    {
      global: {
        plugins: [router],
      },
    },
  )

  await flushPromises()

  return {
    wrapper,
    router,
  }
}

describe('ResourceSearchPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    mockedGetResources.mockResolvedValue(
      result([]),
    )
  })

  it('încarcă biblioteca cu parametrii impliciți', async () => {
    mockedGetResources.mockResolvedValue(
      result([
        resource(1),
      ]),
    )

    const {
      wrapper,
    } = await mountSearch()

    expect(
      mockedGetResources,
    ).toHaveBeenCalledWith({
      q: undefined,
      type: undefined,
      language_code: undefined,
      difficulty_level: undefined,
      complexity_level: undefined,
      page: 1,
      per_page: 20,
      sort: 'published_at',
      direction: 'desc',
    })

    expect(
      wrapper.text(),
    ).toContain('Resursa 1')

    expect(
      wrapper.text(),
    ).toContain('1 resursă')
  })

  it('citește și normalizează filtrele din URL', async () => {
    await mountSearch(
      '/resources' +
        '?q=fracții' +
        '&type=lesson' +
        '&language_code=RO' +
        '&difficulty_level=3' +
        '&complexity_level=4' +
        '&sort=title' +
        '&direction=asc' +
        '&per_page=50' +
        '&page=2',
    )

    expect(
      mockedGetResources,
    ).toHaveBeenLastCalledWith({
      q: 'fracții',
      type: 'lesson',
      language_code: 'ro',
      difficulty_level: 3,
      complexity_level: 4,
      page: 2,
      per_page: 50,
      sort: 'title',
      direction: 'asc',
    })
  })

  it('înlocuiește valorile invalide din URL cu valori sigure', async () => {
    await mountSearch(
      '/resources' +
        '?difficulty_level=11' +
        '&complexity_level=abc' +
        '&sort=invalid' +
        '&direction=invalid' +
        '&per_page=25' +
        '&page=0',
    )

    expect(
      mockedGetResources,
    ).toHaveBeenLastCalledWith({
      q: undefined,
      type: undefined,
      language_code: undefined,
      difficulty_level: undefined,
      complexity_level: undefined,
      page: 1,
      per_page: 20,
      sort: 'published_at',
      direction: 'desc',
    })
  })

  it('aplică filtrele și le sincronizează în URL', async () => {
    const {
      wrapper,
      router,
    } = await mountSearch()

    const searchInput =
      wrapper.get(
        'input[type="search"]',
      )

    const typeInput =
      wrapper.get(
        'input[placeholder="Ex: lecție"]',
      )

    const languageInput =
      wrapper.get(
        'input[placeholder="ro"]',
      )

    const selects =
      wrapper.findAll('select')

    await searchInput.setValue(
      '  energie  ',
    )

    await typeInput.setValue(
      ' lesson ',
    )

    await languageInput.setValue(
      'RO',
    )

    await selects[0]!.setValue('3')
    await selects[1]!.setValue('4')
    await selects[2]!.setValue('title')
    await selects[3]!.setValue('asc')
    await selects[4]!.setValue('50')

    await wrapper
      .get('form')
      .trigger('submit')

    await flushPromises()

    expect(
      router.currentRoute.value.query,
    ).toEqual({
      q: 'energie',
      type: 'lesson',
      language_code: 'ro',
      difficulty_level: '3',
      complexity_level: '4',
      sort: 'title',
      direction: 'asc',
      per_page: '50',
    })

    expect(
      mockedGetResources,
    ).toHaveBeenLastCalledWith({
      q: 'energie',
      type: 'lesson',
      language_code: 'ro',
      difficulty_level: 3,
      complexity_level: 4,
      page: 1,
      per_page: 50,
      sort: 'title',
      direction: 'asc',
    })
  })

  it('resetează filtrele și curăță query string-ul', async () => {
    const {
      wrapper,
      router,
    } = await mountSearch(
      '/resources?q=energie' +
        '&language_code=ro' +
        '&difficulty_level=3',
    )

    expect(
      wrapper.find(
        '.resource-search-reset',
      ).exists(),
    ).toBe(true)

    await wrapper
      .get('.resource-search-reset')
      .trigger('click')

    await flushPromises()

    expect(
      router.currentRoute.value.query,
    ).toEqual({})

    expect(
      mockedGetResources,
    ).toHaveBeenLastCalledWith({
      q: undefined,
      type: undefined,
      language_code: undefined,
      difficulty_level: undefined,
      complexity_level: undefined,
      page: 1,
      per_page: 20,
      sort: 'published_at',
      direction: 'desc',
    })
  })

  it('navighează la pagina următoare păstrând filtrele', async () => {
    mockedGetResources
      .mockResolvedValueOnce(
        result(
          [
            resource(1),
          ],
          1,
          3,
          41,
        ),
      )
      .mockResolvedValueOnce(
        result(
          [
            resource(21),
          ],
          2,
          3,
          41,
        ),
      )

    const {
      wrapper,
      router,
    } = await mountSearch(
      '/resources?q=energie',
    )

    const nextButton =
      wrapper
        .findAll(
          '.resource-pagination > button',
        )
        .find(
          (button) =>
            button.text().includes(
              'Următor',
            ),
        )

    expect(nextButton).toBeDefined()

    await nextButton!.trigger('click')

    await flushPromises()

    expect(
      router.currentRoute.value.query,
    ).toEqual({
      q: 'energie',
      page: '2',
    })

    expect(
      mockedGetResources,
    ).toHaveBeenLastCalledWith({
      q: 'energie',
      type: undefined,
      language_code: undefined,
      difficulty_level: undefined,
      complexity_level: undefined,
      page: 2,
      per_page: 20,
      sort: 'published_at',
      direction: 'desc',
    })

    expect(
      wrapper.text(),
    ).toContain('Resursa 21')
  })

  it('afișează eroarea și permite reîncercarea', async () => {
    mockedGetResources
      .mockRejectedValueOnce(
        new Error('Server indisponibil'),
      )
      .mockResolvedValueOnce(
        result([
          resource(7),
        ]),
      )

    const {
      wrapper,
    } = await mountSearch()

    expect(
      wrapper.get(
        '[role="alert"]',
      ).text(),
    ).toContain(
      'Nu am putut încărca biblioteca.',
    )

    expect(
      wrapper.text(),
    ).toContain(
      'Nu am putut încărca resursele.',
    )

    await wrapper
      .get('.resource-search-retry')
      .trigger('click')

    await flushPromises()

    expect(
      mockedGetResources,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.find(
        '[role="alert"]',
      ).exists(),
    ).toBe(false)

    expect(
      wrapper.text(),
    ).toContain('Resursa 7')
  })

  it('păstrează filtrele când se deschide o resursă', async () => {
    mockedGetResources.mockResolvedValue(
      result([
        resource(9),
      ]),
    )

    const {
      wrapper,
    } = await mountSearch(
      '/resources?q=energie' +
        '&language_code=ro',
    )

    const link =
      wrapper.get(
        '.resource-search-card',
      )

    expect(
      link.attributes('href'),
    ).toContain('/resources/9')

    expect(
      link.attributes('href'),
    ).toContain('q=energie')

    expect(
      link.attributes('href'),
    ).toContain(
      'language_code=ro',
    )
  })
})
