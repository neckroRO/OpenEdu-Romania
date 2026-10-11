import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import {
  getCurriculumSubjectDomains,
} from '../src/api/catalog'

const apiRequestMock = vi.fn()

vi.mock('../src/api/client', () => ({
  apiRequest: (...args: unknown[]) =>
    apiRequestMock(...args),
}))

describe('catalog API', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('încarcă domeniile unei discipline curriculare', async () => {
    apiRequestMock.mockResolvedValue({
      data: [
        {
          id: 10,
          title: 'Algebră',
          description:
            'Concepte fundamentale.',
          display_order: 1,
        },
        {
          id: 11,
          title: 'Geometrie',
          description: null,
          display_order: 2,
        },
      ],
      meta: {
        request_id: 'req-1',
      },
    })

    const domains =
      await getCurriculumSubjectDomains(
        7,
      )

    expect(apiRequestMock)
      .toHaveBeenCalledWith(
        '/curriculum-subjects/7/domains',
      )

    expect(domains).toEqual([
      {
        id: 10,
        title: 'Algebră',
        description:
          'Concepte fundamentale.',
        display_order: 1,
      },
      {
        id: 11,
        title: 'Geometrie',
        description: null,
        display_order: 2,
      },
    ])
  })
})
