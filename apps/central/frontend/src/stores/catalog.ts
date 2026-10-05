import { ref } from 'vue'
import { defineStore } from 'pinia'

import {
  getCurriculumSubjectConcepts,
  getEducationLevels,
  getEducationLevelSubjects,
} from '../api/catalog'

import type {
  ConceptPlacement,
  CurriculumSubject,
  EducationLevel,
} from '../types/catalog'

function getErrorMessage(error: unknown): string {
  if (error instanceof Error) {
    return error.message
  }

  return 'A apărut o eroare neașteptată.'
}

export const useCatalogStore = defineStore('catalog', () => {
  const educationLevels = ref<EducationLevel[]>([])
  const selectedEducationLevel = ref<EducationLevel | null>(null)

  const curriculumSubjects = ref<CurriculumSubject[]>([])
  const selectedCurriculumSubject = ref<CurriculumSubject | null>(null)

  const conceptPlacements = ref<ConceptPlacement[]>([])

  const loading = ref(false)
  const error = ref<string | null>(null)

  function clearError(): void {
    error.value = null
  }

  function resetConcepts(): void {
    conceptPlacements.value = []
  }

  function resetSubjectSelection(): void {
    selectedCurriculumSubject.value = null
    resetConcepts()
  }

  function clearNavigation(): void {
    selectedEducationLevel.value = null
    curriculumSubjects.value = []
    resetSubjectSelection()
    clearError()
  }

  async function loadEducationLevels(): Promise<void> {
    loading.value = true
    clearError()

    try {
      educationLevels.value = await getEducationLevels()
    } catch (caughtError) {
      educationLevels.value = []
      clearNavigation()
      error.value = getErrorMessage(caughtError)
    } finally {
      loading.value = false
    }
  }

  async function selectEducationLevel(
    educationLevel: EducationLevel,
  ): Promise<void> {
    selectedEducationLevel.value = educationLevel

    curriculumSubjects.value = []
    resetSubjectSelection()

    loading.value = true
    clearError()

    try {
      curriculumSubjects.value = await getEducationLevelSubjects(
        educationLevel.id,
      )
    } catch (caughtError) {
      curriculumSubjects.value = []
      error.value = getErrorMessage(caughtError)
    } finally {
      loading.value = false
    }
  }

  async function selectCurriculumSubject(
    curriculumSubject: CurriculumSubject,
  ): Promise<void> {
    selectedCurriculumSubject.value = curriculumSubject
    resetConcepts()

    loading.value = true
    clearError()

    try {
      conceptPlacements.value = await getCurriculumSubjectConcepts(
        curriculumSubject.id,
      )
    } catch (caughtError) {
      conceptPlacements.value = []
      error.value = getErrorMessage(caughtError)
    } finally {
      loading.value = false
    }
  }

  return {
    educationLevels,
    selectedEducationLevel,
    curriculumSubjects,
    selectedCurriculumSubject,
    conceptPlacements,
    loading,
    error,
    loadEducationLevels,
    selectEducationLevel,
    selectCurriculumSubject,
    clearNavigation,
    clearError,
  }
})
