import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import * as client from '../src/api/client'
import {
  approveCurriculumProposal,
  confirmCurriculumMerge,
  createCurriculumProposal,
  getCurriculumMergePreview,
  getCurriculumProposals,
  rejectCurriculumProposal,
} from '../src/api/curriculum'

vi.mock('../src/api/client', () => ({
  apiRequest: vi.fn(),
}))

const mockedApiRequest = vi.mocked(
  client.apiRequest,
)

describe('curriculum API', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('încarcă propunerile curriculare', async () => {
    mockedApiRequest.mockResolvedValue({
      data: [
        {
          id: 1,
          entity_type: 'subject',
          entity_id: null,
          proposal_type: 'create',
          payload: {
            name: 'Educație media',
          },
          reason: 'Materie nouă',
          status: 'pending',
          proposed_by: 10,
          reviewed_by: null,
          reviewed_at: null,
          review_note: null,
          created_at: '2026-10-08T15:00:00Z',
          updated_at: '2026-10-08T15:00:00Z',
        },
      ],
      meta: {
        request_id: null,
      },
    })

    const result =
      await getCurriculumProposals()

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/curriculum/proposals',
    )

    expect(result).toHaveLength(1)

    expect(result[0]).toMatchObject({
      id: 1,
      entity_type: 'subject',
      proposal_type: 'create',
      status: 'pending',
    })
  })

  it('creează o propunere curriculară', async () => {
    const payload = {
      entity_type: 'subject' as const,
      proposal_type: 'create' as const,
      payload: {
        name: 'Educație media',
      },
      reason: 'Materie nouă',
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        id: 2,
        ...payload,
        entity_id: null,
        status: 'pending',
        proposed_by: 10,
        reviewed_by: null,
        reviewed_at: null,
        review_note: null,
        created_at: null,
        updated_at: null,
      },
      meta: {
        request_id: null,
      },
    })

    const result =
      await createCurriculumProposal(
        payload,
      )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/curriculum/proposals',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
    )

    expect(result.id).toBe(2)
    expect(result.status).toBe('pending')
  })

  it('încarcă preview-ul unui merge', async () => {
    mockedApiRequest.mockResolvedValue({
      data: {
        source_subject_id: 5,
        target_subject_id: 3,
        curriculum_subjects_to_move: 2,
        curriculum_subject_collisions: 0,
        aliases_to_move: 1,
        aliases_to_deduplicate: 0,
        source_name_alias_will_be_created: true,
        reputations_to_move: 1,
        reputations_to_consolidate: 0,
        reputation_events_to_move: 4,
        blocked: false,
        blocking_reasons: [],
      },
      meta: {
        request_id: null,
      },
    })

    const result =
      await getCurriculumMergePreview(12)

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/curriculum/proposals/12/merge-preview',
    )

    expect(result.blocked).toBe(false)
    expect(result.source_subject_id).toBe(5)
    expect(result.target_subject_id).toBe(3)
  })

  it('încarcă preview-ul unui merge de concepte', async () => {
    mockedApiRequest.mockResolvedValue({
      data: {
        source_concept_id: 15,
        target_concept_id: 8,
        placements_to_move: 2,
        placements_to_deduplicate: 1,
        competencies_to_move: 3,
        competencies_to_deduplicate: 1,
        lessons_to_move: 4,
        lessons_to_deduplicate: 2,
        resources_to_move: 5,
        resources_to_deduplicate: 1,
        aliases_to_move: 2,
        aliases_to_deduplicate: 1,
        source_title_alias_will_be_created: true,
        blocked: false,
        blocking_reasons: [],
      },
      meta: {
        request_id: null,
      },
    })

    const result =
      await getCurriculumMergePreview(21)

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/curriculum/proposals/21/merge-preview',
    )

    expect(result.blocked).toBe(false)

    expect(
      'source_concept_id' in result
        ? result.source_concept_id
        : null,
    ).toBe(15)

    expect(
      'target_concept_id' in result
        ? result.target_concept_id
        : null,
    ).toBe(8)
  })

  it('aprobă o propunere curriculară', async () => {
    const payload = {
      note: 'Propunere validată pedagogic.',
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        id: 20,
        entity_type: 'domain',
        entity_id: null,
        proposal_type: 'create',
        payload: {
          curriculum_subject_id: 4,
          title: 'Algebră',
          description: null,
          parent_domain_id: null,
          display_order: 10,
        },
        reason: 'Domeniu curricular nou',
        status: 'approved',
        proposed_by: 10,
        reviewed_by: 2,
        reviewed_at: '2026-10-08T17:00:00Z',
        review_note: payload.note,
        created_at: null,
        updated_at: null,
      },
      meta: {
        request_id: null,
      },
    })

    const result =
      await approveCurriculumProposal(
        20,
        payload,
      )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/curriculum/proposals/20/approve',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
    )

    expect(result.entity_type).toBe('domain')
    expect(result.status).toBe('approved')
  })

  it('respinge o propunere', async () => {
    const payload = {
      note: 'Propunerea dublează o materie existentă.',
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        id: 8,
        entity_type: 'subject',
        entity_id: 5,
        proposal_type: 'update',
        payload: {
          name: 'Matematică',
        },
        reason: null,
        status: 'rejected',
        proposed_by: 10,
        reviewed_by: 2,
        reviewed_at: '2026-10-08T15:10:00Z',
        review_note: payload.note,
        created_at: null,
        updated_at: null,
      },
      meta: {
        request_id: null,
      },
    })

    const result =
      await rejectCurriculumProposal(
        8,
        payload,
      )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/curriculum/proposals/8/reject',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
    )

    expect(result.status).toBe('rejected')
  })

  it('confirmă un merge curricular', async () => {
    const payload = {
      note: 'Duplicate confirmat.',
    }

    mockedApiRequest.mockResolvedValue({
      data: {
        proposal: {
          id: 15,
          entity_type: 'subject',
          entity_id: 9,
          proposal_type: 'merge_candidate',
          payload: {
            duplicate_entity_id: 3,
          },
          reason: null,
          status: 'merged',
          proposed_by: 10,
          reviewed_by: 2,
          reviewed_at: '2026-10-08T15:20:00Z',
          review_note: payload.note,
          created_at: null,
          updated_at: null,
        },
        merge: {
          id: 4,
          entity_type: 'subject',
          source_entity_id: 9,
          target_entity_id: 3,
          merged_by: 2,
          merged_at: '2026-10-08T15:20:00Z',
          reason: payload.note,
          metadata: {},
        },
      },
      meta: {
        request_id: null,
      },
    })

    const result =
      await confirmCurriculumMerge(
        15,
        payload,
      )

    expect(
      mockedApiRequest,
    ).toHaveBeenCalledWith(
      '/curriculum/proposals/15/confirm-merge',
      {
        method: 'POST',
        body: JSON.stringify(payload),
      },
    )

    expect(result.proposal.status).toBe(
      'merged',
    )

    expect(
      result.merge.source_entity_id,
    ).toBe(9)

    expect(
      result.merge.target_entity_id,
    ).toBe(3)
  })
})
