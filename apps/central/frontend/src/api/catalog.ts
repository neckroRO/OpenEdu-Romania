import { apiRequest } from './client'

import type {
  ApiResponse,
  ConceptPlacement,
  CurriculumSubject,
  EducationLevel,
} from '../types/catalog'

export async function getEducationLevels(): Promise<EducationLevel[]> {
  const response =
    await apiRequest<ApiResponse<EducationLevel[]>>('/education-levels')

  return response.data
}

export async function getEducationLevelSubjects(
  educationLevelId: number,
): Promise<CurriculumSubject[]> {
  const response = await apiRequest<ApiResponse<CurriculumSubject[]>>(
    `/education-levels/${educationLevelId}/subjects`,
  )

  return response.data
}

export async function getCurriculumSubjectConcepts(
  curriculumSubjectId: number,
): Promise<ConceptPlacement[]> {
  const response = await apiRequest<ApiResponse<ConceptPlacement[]>>(
    `/curriculum-subjects/${curriculumSubjectId}/concepts`,
  )

  return response.data
}
