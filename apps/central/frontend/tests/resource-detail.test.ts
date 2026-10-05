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
import { ApiError } from '../src/api/client'
import ResourceDetailView from '../src/views/ResourceDetailView.vue'

import type {
  EducationalResourceDetail,
} from '../src/types/resource'

vi.mock('../src/api/resources', () => ({
  getResource: vi.fn(),
  getResources: vi.fn(),
}))

const mockedGetResource = vi.mocked(
  resourcesApi.getResource,
)

function resource(
  overrides: Partial<EducationalResourceDetail> = {},
): EducationalResourceDetail {
  return {
    id: 7,
    code: 'RES-7',
    type: 'lesson',
    version: {
      id: 107,
      version_number: 2,
      title: 'Fracții echivalente',
      summary: 'Rezumat de test',
      language_code: 'ro',
      difficulty_level: 3,
      complexity_level: 4,
      published_at: '2026-10-01T12:00:00Z',
      content: 'Conținut educațional de test.',
      source_url: 'https://example.com/source',
    },
    ...overrides,
  }
}

async function mountDetail(
  initialPath = '/resources/7',
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
        component: ResourceDetailView,
      },
    ],
  })

  await router.push(initialPath)
  await router.isReady()

  const wrapper = mount(
    ResourceDetailView,
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

describe('ResourceDetailView', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    mockedGetResource.mockResolvedValue(
      resource(),
    )
  })

  it('încarcă resursa pe baza ID-ului din rută', async () => {
    const {
      wrapper,
    } = await mountDetail(
      '/resources/7',
    )

    expect(
      mockedGetResource,
    ).toHaveBeenCalledWith(7)

    expect(
      wrapper.text(),
    ).toContain('Fracții echivalente')

    expect(
      wrapper.text(),
    ).toContain(
      'Conținut educațional de test.',
    )

    expect(
      wrapper.text(),
    ).toContain('RES-7')
  })

  it('afișează metadatele resursei', async () => {
    const {
      wrapper,
    } = await mountDetail()

    expect(
      wrapper.text(),
    ).toContain('RO')

    expect(
      wrapper.text(),
    ).toContain('3/10')

    expect(
      wrapper.text(),
    ).toContain('4/10')

    expect(
      wrapper.text(),
    ).toContain('Versiune')

    expect(
      wrapper.text(),
    ).toContain('2')
  })

  it('nu face request pentru un ID invalid', async () => {
    const {
      wrapper,
    } = await mountDetail(
      '/resources/abc',
    )

    expect(
      mockedGetResource,
    ).not.toHaveBeenCalled()

    expect(
      wrapper.text(),
    ).toContain(
      'Resursa nu este disponibilă.',
    )

    expect(
      wrapper.text(),
    ).toContain('404')
  })

  it('tratează ID-ul zero ca resursă inexistentă', async () => {
    const {
      wrapper,
    } = await mountDetail(
      '/resources/0',
    )

    expect(
      mockedGetResource,
    ).not.toHaveBeenCalled()

    expect(
      wrapper.text(),
    ).toContain(
      'Resursa nu este disponibilă.',
    )
  })

  it('afișează starea 404 pentru răspuns API 404', async () => {
    mockedGetResource.mockRejectedValue(
      new ApiError(404, null),
    )

    const {
      wrapper,
    } = await mountDetail()

    expect(
      wrapper.text(),
    ).toContain(
      'Resursa nu este disponibilă.',
    )

    expect(
      wrapper.find(
        '[role="alert"]',
      ).exists(),
    ).toBe(false)
  })

  it('consideră indisponibilă o resursă fără versiune publicată', async () => {
    mockedGetResource.mockResolvedValue(
      resource({
        version: null,
      }),
    )

    const {
      wrapper,
    } = await mountDetail()

    expect(
      wrapper.text(),
    ).toContain(
      'Resursa nu este disponibilă.',
    )

    expect(
      wrapper.text(),
    ).not.toContain(
      'Fracții echivalente',
    )
  })

  it('afișează eroarea de request și permite retry', async () => {
    mockedGetResource
      .mockRejectedValueOnce(
        new ApiError(500, null),
      )
      .mockResolvedValueOnce(
        resource(),
      )

    const {
      wrapper,
    } = await mountDetail()

    expect(
      wrapper.get(
        '[role="alert"]',
      ).text(),
    ).toContain(
      'Nu am putut încărca resursa.',
    )

    const retryButton =
      wrapper.get(
        '.resource-state-action',
      )

    await retryButton.trigger('click')

    await flushPromises()

    expect(
      mockedGetResource,
    ).toHaveBeenCalledTimes(2)

    expect(
      wrapper.find(
        '[role="alert"]',
      ).exists(),
    ).toBe(false)

    expect(
      wrapper.text(),
    ).toContain('Fracții echivalente')
  })

  it('afișează starea pentru conținut textual lipsă', async () => {
    mockedGetResource.mockResolvedValue(
      resource({
        version: {
          ...resource().version!,
          content: null,
        },
      }),
    )

    const {
      wrapper,
    } = await mountDetail()

    expect(
      wrapper.text(),
    ).toContain(
      'Această resursă nu conține momentan material textual',
    )
  })

  it('afișează doar sursele externe HTTP sau HTTPS valide', async () => {
    const {
      wrapper,
    } = await mountDetail()

    const sourceLink =
      wrapper.get(
        '.resource-source-link',
      )

    expect(
      sourceLink.attributes('href'),
    ).toBe(
      'https://example.com/source',
    )

    mockedGetResource.mockResolvedValue(
      resource({
        version: {
          ...resource().version!,
          source_url:
            'javascript:alert(1)',
        },
      }),
    )

    const secondMount =
      await mountDetail(
        '/resources/8',
      )

    expect(
      secondMount.wrapper.find(
        '.resource-source-link',
      ).exists(),
    ).toBe(false)
  })

  it('păstrează filtrele când utilizatorul revine la catalog', async () => {
    const {
      wrapper,
    } = await mountDetail(
      '/resources/7?q=energie&language_code=ro&page=2',
    )

    const backLink =
      wrapper.get(
        '.resource-back',
      )

    const href =
      backLink.attributes('href')

    expect(href).toContain('/resources')
    expect(href).toContain('q=energie')
    expect(href).toContain(
      'language_code=ro',
    )
    expect(href).toContain('page=2')
  })

  it('reîncarcă resursa când se schimbă ID-ul din rută', async () => {
    mockedGetResource
      .mockResolvedValueOnce(
        resource({
          id: 7,
          code: 'RES-7',
        }),
      )
      .mockResolvedValueOnce(
        resource({
          id: 8,
          code: 'RES-8',
          version: {
            ...resource().version!,
            title: 'A doua resursă',
          },
        }),
      )

    const {
      wrapper,
      router,
    } = await mountDetail(
      '/resources/7',
    )

    expect(
      wrapper.text(),
    ).toContain('RES-7')

    await router.push(
      '/resources/8',
    )

    await flushPromises()

    expect(
      mockedGetResource,
    ).toHaveBeenLastCalledWith(8)

    expect(
      wrapper.text(),
    ).toContain('RES-8')

    expect(
      wrapper.text(),
    ).toContain(
      'A doua resursă',
    )
  })
})
