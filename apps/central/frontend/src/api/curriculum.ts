import { apiRequest } from './client'

import type { ApiResponse } from '../types/catalog'
import type {
  CreateCurriculumProposalPayload,
  CurriculumMergePreview,
  CurriculumMergeResult,
  CurriculumProposal,
  ReviewCurriculumProposalPayload,
} from '../types/curriculum'

export async function getCurriculumProposals():
Promise<CurriculumProposal[]> {
  const response = await apiRequest<
    ApiResponse<CurriculumProposal[]>
  >('/curriculum/proposals')

  return response.data
}

export async function createCurriculumProposal(
  payload: CreateCurriculumProposalPayload,
): Promise<CurriculumProposal> {
  const response = await apiRequest<
    ApiResponse<CurriculumProposal>
  >('/curriculum/proposals', {
    method: 'POST',
    body: JSON.stringify(payload),
  })

  return response.data
}

export async function getCurriculumMergePreview(
  proposalId: number,
): Promise<CurriculumMergePreview> {
  const response = await apiRequest<
    ApiResponse<CurriculumMergePreview>
  >(
    `/curriculum/proposals/${proposalId}/merge-preview`,
  )

  return response.data
}

export async function rejectCurriculumProposal(
  proposalId: number,
  payload: ReviewCurriculumProposalPayload = {},
): Promise<CurriculumProposal> {
  const response = await apiRequest<
    ApiResponse<CurriculumProposal>
  >(
    `/curriculum/proposals/${proposalId}/reject`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  )

  return response.data
}

export async function confirmCurriculumMerge(
  proposalId: number,
  payload: ReviewCurriculumProposalPayload = {},
): Promise<CurriculumMergeResult> {
  const response = await apiRequest<
    ApiResponse<CurriculumMergeResult>
  >(
    `/curriculum/proposals/${proposalId}/confirm-merge`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  )

  return response.data
}
