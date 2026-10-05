import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import * as client from '../src/api/client'
import {
  approveResourceVersion,
  createEditorialResource,
  getEditorialResources,
  getModerationQueue,
  publishResourceVersion,
  rejectResourceVersion,
  reviseResourceVersion,
  submitResourceVersion,
  updateEditorialResource,
} from '../src/api/editorial'

vi.mock('../src/api/client', () => ({
  apiRequest: vi.fn(),
  ApiError: class ApiError extends Error {},
}))

const mockedApiRequest = vi.mocked(
  client.apiRequest,
)

describe('editorial API', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('încarcă resursele editoriale ale utilizatorului', async () => {
    mockedApiRequest.mockResolvedValue({
      data: [
        {
          id: 1,
          code: 'RES-1',
        },
      ],
      meta: {
        request_id: null,
      },
    })

    const result =
      await getEditorialResources()

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/editor/resources',
    )

    expect(result).toEqual([
      {
        id: 1,
        code: 'RES-1',
      },
    ])
  })

  it('creează o resursă prin POST /resources', async () => {
    const payload = {
      concept_id: 200,
      type: 'lesson',
      title: 'Fracții',
      summary: 'Rezumat',
      content: 'Conținut',
      source_url: null,
      language_code: 'ro',
      difficulty_level: 3,
      complexity_level: 4,
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        id: 7,
      },
      meta: {
        request_id: null,
      },
    })

    await createEditorialResource(
      payload,
    )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/resources',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
    )
  })

  it('actualizează resursa prin PATCH /resources/:id', async () => {
    const payload = {
      title: 'Titlu actualizat',
      summary: null,
      content: 'Conținut actualizat',
      source_url: null,
      language_code: 'ro',
      difficulty_level: 4,
      complexity_level: 5,
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        id: 9,
      },
      meta: {
        request_id: null,
      },
    })

    await updateEditorialResource(
      9,
      payload,
    )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/resources/9',
      {
        method: 'PATCH',
        body: JSON.stringify(payload),
      },
    )
  })

  it('trimite versiunea la validare prin endpointul submit', async () => {
    mockedApiRequest.mockResolvedValue({
      data: {
        id: 101,
        status: 'submitted',
      },
      meta: {
        request_id: null,
      },
    })

    await submitResourceVersion(101)

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/resource-versions/101/submit',
      {
        method: 'POST',
      },
    )
  })

  it('readuce versiunea respinsă în draft prin endpointul revise', async () => {
    mockedApiRequest.mockResolvedValue({
      data: {
        id: 101,
        status: 'draft',
      },
      meta: {
        request_id: null,
      },
    })

    await reviseResourceVersion(101)

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/resource-versions/101/revise',
      {
        method: 'POST',
      },
    )
  })

  it('încarcă coada de moderare', async () => {
    mockedApiRequest.mockResolvedValue({
      data: [
        {
          id: 101,
          status: 'submitted',
        },
      ],
      meta: {
        request_id: null,
      },
    })

    const result =
      await getModerationQueue()

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/editor/moderation',
    )

    expect(result).toEqual([
      {
        id: 101,
        status: 'submitted',
      },
    ])
  })

  it('aprobă o versiune prin endpointul approve', async () => {
    mockedApiRequest.mockResolvedValue({
      data: null,
      meta: {
        request_id: null,
      },
    })

    await approveResourceVersion(101)

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/resource-versions/101/approve',
      {
        method: 'POST',
      },
    )
  })

  it('respinge o versiune cu feedback prin endpointul reject', async () => {
    mockedApiRequest.mockResolvedValue({
      data: null,
      meta: {
        request_id: null,
      },
    })

    await rejectResourceVersion(
      101,
      'Completează explicația.',
    )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/resource-versions/101/reject',
      {
        method: 'POST',
        body: JSON.stringify({
          review_note:
            'Completează explicația.',
        }),
      },
    )
  })

  it('publică o versiune prin endpointul publish', async () => {
    mockedApiRequest.mockResolvedValue({
      data: null,
      meta: {
        request_id: null,
      },
    })

    await publishResourceVersion(101)

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/resource-versions/101/publish',
      {
        method: 'POST',
      },
    )
  })

})
