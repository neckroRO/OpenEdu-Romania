import { ref } from 'vue'
import { defineStore } from 'pinia'

import {
  getConceptResources,
  getCurriculumSubjectConcepts,
  getEducationLevels,
  getEducationLevelSubjects,
} from '../api/catalog'

import type {
  ConceptPlacement,
  ConceptResourceLink,
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
  const selectedConceptPlacement = ref<ConceptPlacement | null>(null)

  const conceptResources = ref<ConceptResourceLink[]>([])

  const loading = ref(false)
  const resourcesLoading = ref(false)

  const error = ref<string | null>(null)
  const resourcesError = ref<string | null>(null)

  let resourceRequestId = 0

  function clearError(): void {
    error.value = null
  }

  function clearResourcesError(): void {
    resourcesError.value = null
  }

  function resetResources(): void {
    resourceRequestId += 1

    selectedConceptPlacement.value = null
    conceptResources.value = []
    resourcesLoading.value = false
    clearResourcesError()
  }

  function resetConcepts(): void {
    conceptPlacements.value = []
    resetResources()
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

  async function selectConcept(
    conceptPlacement: ConceptPlacement,
  ): Promise<void> {
    const currentRequestId = ++resourceRequestId

    selectedConceptPlacement.value = conceptPlacement
    conceptResources.value = []

    resourcesLoading.value = true
    clearResourcesError()

    try {
      const resources = await getConceptResources(
        conceptPlacement.concept.id,
      )

      if (currentRequestId !== resourceRequestId) {
        return
      }

      conceptResources.value = resources
    } catch (caughtError) {
      if (currentRequestId !== resourceRequestId) {
        return
      }

      conceptResources.value = []
      resourcesError.value = getErrorMessage(caughtError)
    } finally {
      if (currentRequestId === resourceRequestId) {
        resourcesLoading.value = false
      }
    }
  }

  return {
    educationLevels,
    selectedEducationLevel,
    curriculumSubjects,
    selectedCurriculumSubject,
    conceptPlacements,
    selectedConceptPlacement,
    conceptResources,
    loading,
    resourcesLoading,
    error,
    resourcesError,
    loadEducationLevels,
    selectEducationLevel,
    selectCurriculumSubject,
    selectConcept,
    clearNavigation,
    clearError,
    clearResourcesError,
  }
})
