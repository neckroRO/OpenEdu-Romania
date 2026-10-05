<script setup lang="ts">
import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'

import {
  getCurriculumSubjectConcepts,
  getEducationLevels,
  getEducationLevelSubjects,
} from '../api/catalog'
import { ApiError } from '../api/client'
import {
  createEditorialResource,
  getEditorialResources,
  reviseResourceVersion,
  submitResourceVersion,
  updateEditorialResource,
} from '../api/editorial'
import { useAuthStore } from '../stores/auth'

import type {
  ConceptPlacement,
  CurriculumSubject,
  EducationLevel,
} from '../types/catalog'
import type {
  EditorialConcept,
  EditorialResource,
  EditorialStatus,
} from '../types/editorial'

type EditorMode = 'create' | 'edit'

const router = useRouter()
const authStore = useAuthStore()

const resources = ref<EditorialResource[]>([])
const educationLevels = ref<EducationLevel[]>([])
const curriculumSubjects = ref<CurriculumSubject[]>([])
const conceptPlacements = ref<ConceptPlacement[]>([])

const workspaceLoading = ref(false)
const catalogLoading = ref(false)
const editorSaving = ref(false)
const actionResourceId = ref<number | null>(null)

const workspaceError = ref<string | null>(null)
const editorError = ref<string | null>(null)
const notice = ref<string | null>(null)

const editorMode = ref<EditorMode>('create')
const editingResourceId = ref<number | null>(null)
const currentConcept = ref<EditorialConcept | null>(null)

const selectedEducationLevelId = ref('')
const selectedCurriculumSubjectId = ref('')
const selectedConceptId = ref('')

const form = reactive({
  type: 'explanation',
  title: '',
  summary: '',
  content: '',
  sourceUrl: '',
  languageCode: 'ro',
  difficultyLevel: '1',
  complexityLevel: '1',
})

const stats = computed(() => ({
  total: resources.value.length,
  draft: resources.value.filter(
    (resource) => resource.version?.status === 'draft',
  ).length,
  submitted: resources.value.filter(
    (resource) => resource.version?.status === 'submitted',
  ).length,
  published: resources.value.filter(
    (resource) => resource.version?.status === 'published',
  ).length,
}))

function statusLabel(status: EditorialStatus): string {
  const labels: Record<EditorialStatus, string> = {
    draft: 'Draft',
    submitted: 'Trimisă',
    rejected: 'Respinsă',
    approved: 'Aprobată',
    published: 'Publicată',
  }

  return labels[status]
}

function formatDate(value: string | null): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return new Intl.DateTimeFormat('ro-RO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

function requestErrorMessage(
  error: unknown,
  fallback: string,
): string {
  if (error instanceof ApiError) {
    if (error.status === 401) {
      return 'Sesiunea a expirat. Autentifică-te din nou.'
    }

    if (error.status === 403) {
      return 'Nu ai permisiunea necesară pentru această acțiune.'
    }

    if (error.status === 409) {
      return 'Acțiunea nu mai este disponibilă în starea curentă.'
    }

    if (error.status === 422) {
      return 'Verifică datele completate în formular.'
    }
  }

  return fallback
}

async function handleExpiredSession(
  error: unknown,
): Promise<boolean> {
  if (
    error instanceof ApiError &&
    error.status === 401
  ) {
    await authStore.logout()

    await router.push({
      name: 'login',
      query: {
        redirect: '/teacher',
      },
    })

    return true
  }

  return false
}

async function loadResources(): Promise<void> {
  workspaceLoading.value = true
  workspaceError.value = null

  try {
    resources.value = await getEditorialResources()
  } catch (error) {
    if (await handleExpiredSession(error)) {
      return
    }

    resources.value = []
    workspaceError.value = requestErrorMessage(
      error,
      'Nu am putut încărca resursele tale.',
    )
  } finally {
    workspaceLoading.value = false
  }
}

async function loadEducationLevels(): Promise<void> {
  catalogLoading.value = true

  try {
    educationLevels.value = await getEducationLevels()
  } catch {
    educationLevels.value = []
  } finally {
    catalogLoading.value = false
  }
}

async function onEducationLevelChange(): Promise<void> {
  selectedCurriculumSubjectId.value = ''
  selectedConceptId.value = ''
  curriculumSubjects.value = []
  conceptPlacements.value = []

  if (!selectedEducationLevelId.value) {
    return
  }

  catalogLoading.value = true

  try {
    curriculumSubjects.value =
      await getEducationLevelSubjects(
        Number(selectedEducationLevelId.value),
      )
  } catch {
    curriculumSubjects.value = []
  } finally {
    catalogLoading.value = false
  }
}

async function onCurriculumSubjectChange(): Promise<void> {
  selectedConceptId.value = ''
  conceptPlacements.value = []

  if (!selectedCurriculumSubjectId.value) {
    return
  }

  catalogLoading.value = true

  try {
    conceptPlacements.value =
      await getCurriculumSubjectConcepts(
        Number(selectedCurriculumSubjectId.value),
      )
  } catch {
    conceptPlacements.value = []
  } finally {
    catalogLoading.value = false
  }
}

function clearConceptSelection(): void {
  selectedEducationLevelId.value = ''
  selectedCurriculumSubjectId.value = ''
  selectedConceptId.value = ''
  curriculumSubjects.value = []
  conceptPlacements.value = []
}

function resetForm(): void {
  editorMode.value = 'create'
  editingResourceId.value = null
  currentConcept.value = null
  editorError.value = null

  form.type = 'explanation'
  form.title = ''
  form.summary = ''
  form.content = ''
  form.sourceUrl = ''
  form.languageCode = 'ro'
  form.difficultyLevel = '1'
  form.complexityLevel = '1'

  clearConceptSelection()
}

function startEdit(resource: EditorialResource): void {
  if (
    !resource.version ||
    resource.version.status !== 'draft'
  ) {
    return
  }

  editorMode.value = 'edit'
  editingResourceId.value = resource.id
  currentConcept.value = resource.concept
  editorError.value = null
  notice.value = null

  form.type = resource.type
  form.title = resource.version.title
  form.summary = resource.version.summary ?? ''
  form.content = resource.version.content ?? ''
  form.sourceUrl = resource.version.source_url ?? ''
  form.languageCode = resource.version.language_code
  form.difficultyLevel = String(
    resource.version.difficulty_level,
  )
  form.complexityLevel = String(
    resource.version.complexity_level,
  )

  clearConceptSelection()

  requestAnimationFrame(() => {
    document
      .querySelector('#teacher-editor')
      ?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      })
  })
}

async function saveResource(): Promise<void> {
  editorError.value = null
  notice.value = null

  const title = form.title.trim()
  const type = form.type.trim()
  const languageCode =
    form.languageCode.trim().toLowerCase()

  if (!title || !type) {
    editorError.value =
      'Titlul și tipul resursei sunt obligatorii.'
    return
  }

  if (!/^[a-z]{2}$/.test(languageCode)) {
    editorError.value =
      'Codul limbii trebuie să conțină două litere.'
    return
  }

  if (
    editorMode.value === 'create' &&
    !selectedConceptId.value
  ) {
    editorError.value =
      'Selectează conceptul principal al resursei.'
    return
  }

  editorSaving.value = true

  const payload = {
    type,
    title,
    summary: form.summary.trim() || null,
    content: form.content.trim() || null,
    source_url: form.sourceUrl.trim() || null,
    language_code: languageCode,
    difficulty_level: Number(
      form.difficultyLevel,
    ),
    complexity_level: Number(
      form.complexityLevel,
    ),
  }

  try {
    if (editorMode.value === 'create') {
      await createEditorialResource({
        ...payload,
        concept_id: Number(
          selectedConceptId.value,
        ),
      })

      notice.value =
        'Resursa a fost creată și salvată ca draft.'
    } else if (editingResourceId.value) {
      await updateEditorialResource(
        editingResourceId.value,
        {
          ...payload,
          ...(selectedConceptId.value
            ? {
                concept_id: Number(
                  selectedConceptId.value,
                ),
              }
            : {}),
        },
      )

      notice.value =
        'Draftul a fost actualizat.'
    }

    resetForm()
    await loadResources()
  } catch (error) {
    if (await handleExpiredSession(error)) {
      return
    }

    editorError.value = requestErrorMessage(
      error,
      'Resursa nu a putut fi salvată.',
    )
  } finally {
    editorSaving.value = false
  }
}

async function submitVersion(
  resource: EditorialResource,
): Promise<void> {
  if (
    !resource.version ||
    resource.version.status !== 'draft'
  ) {
    return
  }

  actionResourceId.value = resource.id
  workspaceError.value = null
  notice.value = null

  try {
    await submitResourceVersion(
      resource.version.id,
    )

    if (
      editingResourceId.value === resource.id
    ) {
      resetForm()
    }

    notice.value =
      'Resursa a fost trimisă pentru validare.'

    await loadResources()
  } catch (error) {
    if (await handleExpiredSession(error)) {
      return
    }

    workspaceError.value = requestErrorMessage(
      error,
      'Resursa nu a putut fi trimisă.',
    )
  } finally {
    actionResourceId.value = null
  }
}

async function reviseVersion(
  resource: EditorialResource,
): Promise<void> {
  if (
    !resource.version ||
    resource.version.status !== 'rejected'
  ) {
    return
  }

  actionResourceId.value = resource.id
  workspaceError.value = null
  notice.value = null

  try {
    await reviseResourceVersion(
      resource.version.id,
    )

    notice.value =
      'Resursa a revenit în starea draft și poate fi editată.'

    await loadResources()

    const refreshed = resources.value.find(
      (item) => item.id === resource.id,
    )

    if (refreshed) {
      startEdit(refreshed)
    }
  } catch (error) {
    if (await handleExpiredSession(error)) {
      return
    }

    workspaceError.value = requestErrorMessage(
      error,
      'Resursa nu a putut reveni la draft.',
    )
  } finally {
    actionResourceId.value = null
  }
}

onMounted(() => {
  void Promise.all([
    loadResources(),
    loadEducationLevels(),
  ])
})
</script>

<template>
  <section class="page teacher-page">
    <header class="teacher-heading">
      <div>
        <span class="eyebrow">CONTRIBUITORI</span>

        <h1>Zona profesorului</h1>

        <p>
          Creează resurse educaționale, gestionează drafturile
          și trimite materialele pentru validare.
        </p>
      </div>

      <button
        type="button"
        class="teacher-new-button"
        @click="resetForm"
      >
        + Resursă nouă
      </button>
    </header>

    <div class="teacher-stats">
      <div>
        <span>Total</span>
        <strong>{{ stats.total }}</strong>
      </div>

      <div>
        <span>Draft</span>
        <strong>{{ stats.draft }}</strong>
      </div>

      <div>
        <span>În validare</span>
        <strong>{{ stats.submitted }}</strong>
      </div>

      <div>
        <span>Publicate</span>
        <strong>{{ stats.published }}</strong>
      </div>
    </div>

    <div
      v-if="notice"
      class="teacher-notice"
      role="status"
    >
      {{ notice }}
    </div>

    <div
      v-if="workspaceError"
      class="teacher-error"
      role="alert"
    >
      <span>{{ workspaceError }}</span>

      <button
        type="button"
        :disabled="workspaceLoading"
        @click="loadResources"
      >
        Reîncearcă
      </button>
    </div>

    <div class="teacher-layout">
      <section class="teacher-resources">
        <div class="teacher-section-heading">
          <div>
            <span>RESURSELE MELE</span>
            <h2>Activitate editorială</h2>
          </div>

          <span>{{ resources.length }}</span>
        </div>

        <div
          v-if="workspaceLoading && resources.length === 0"
          class="teacher-empty"
        >
          Se încarcă resursele...
        </div>

        <div
          v-else-if="resources.length === 0"
          class="teacher-empty"
        >
          <strong>Nu ai încă resurse.</strong>
          <span>
            Creează primul material folosind formularul alăturat.
          </span>
        </div>

        <div
          v-else
          class="teacher-resource-list"
        >
          <article
            v-for="resource in resources"
            :key="resource.id"
            class="teacher-resource-card"
          >
            <div class="teacher-resource-top">
              <span class="teacher-resource-type">
                {{ resource.type }}
              </span>

              <span
                v-if="resource.version"
                class="teacher-status"
                :class="`status-${resource.version.status}`"
              >
                {{ statusLabel(resource.version.status) }}
              </span>
            </div>

            <h3>
              {{
                resource.version?.title ??
                resource.code
              }}
            </h3>

            <p v-if="resource.version?.summary">
              {{ resource.version.summary }}
            </p>

            <div class="teacher-resource-meta">
              <span>{{ resource.code }}</span>

              <span v-if="resource.concept">
                {{ resource.concept.title }}
              </span>

              <span v-if="resource.version">
                Versiunea
                {{ resource.version.version_number }}
              </span>
            </div>

            <div
              v-if="
                resource.version?.status === 'rejected' &&
                resource.version.review_note
              "
              class="teacher-review-note"
            >
              <strong>Feedback moderator</strong>
              <span>
                {{ resource.version.review_note }}
              </span>
            </div>

            <div
              v-if="resource.version"
              class="teacher-resource-dates"
            >
              <span v-if="resource.version.submitted_at">
                Trimisă:
                {{
                  formatDate(
                    resource.version.submitted_at,
                  )
                }}
              </span>

              <span v-if="resource.version.published_at">
                Publicată:
                {{
                  formatDate(
                    resource.version.published_at,
                  )
                }}
              </span>
            </div>

            <div class="teacher-resource-actions">
              <button
                v-if="resource.version?.status === 'draft'"
                type="button"
                class="secondary-action"
                @click="startEdit(resource)"
              >
                Editează
              </button>

              <button
                v-if="resource.version?.status === 'draft'"
                type="button"
                class="primary-action"
                :disabled="
                  actionResourceId === resource.id
                "
                @click="submitVersion(resource)"
              >
                {{
                  actionResourceId === resource.id
                    ? 'Se trimite...'
                    : 'Trimite la validare'
                }}
              </button>

              <button
                v-if="resource.version?.status === 'rejected'"
                type="button"
                class="primary-action"
                :disabled="
                  actionResourceId === resource.id
                "
                @click="reviseVersion(resource)"
              >
                {{
                  actionResourceId === resource.id
                    ? 'Se pregătește...'
                    : 'Revizuiește'
                }}
              </button>
            </div>
          </article>
        </div>
      </section>

      <section
        id="teacher-editor"
        class="teacher-editor"
      >
        <div class="teacher-section-heading">
          <div>
            <span>
              {{
                editorMode === 'create'
                  ? 'RESURSĂ NOUĂ'
                  : 'EDITARE DRAFT'
              }}
            </span>

            <h2>
              {{
                editorMode === 'create'
                  ? 'Creează material'
                  : 'Actualizează materialul'
              }}
            </h2>
          </div>

          <button
            v-if="editorMode === 'edit'"
            type="button"
            class="editor-cancel"
            @click="resetForm"
          >
            Anulează
          </button>
        </div>

        <form
          class="teacher-form"
          @submit.prevent="saveResource"
        >
          <div class="teacher-form-row">
            <label>
              <span>Titlu *</span>
              <input
                v-model="form.title"
                type="text"
                maxlength="255"
                required
                placeholder="Titlul resursei"
              >
            </label>

            <label>
              <span>Tip *</span>

              <input
                v-model="form.type"
                type="text"
                maxlength="64"
                list="resource-type-options"
                required
                placeholder="explanation"
              >

              <datalist id="resource-type-options">
                <option value="explanation" />
                <option value="lesson" />
                <option value="exercise" />
                <option value="quiz" />
                <option value="reference" />
              </datalist>
            </label>
          </div>

          <label>
            <span>Rezumat</span>

            <textarea
              v-model="form.summary"
              rows="3"
              placeholder="Descriere scurtă a resursei"
            />
          </label>

          <label>
            <span>Conținut</span>

            <textarea
              v-model="form.content"
              rows="12"
              placeholder="Conținutul educațional..."
            />
          </label>

          <label>
            <span>Sursă externă</span>

            <input
              v-model="form.sourceUrl"
              type="url"
              maxlength="2048"
              placeholder="https://..."
            >
          </label>

          <div class="teacher-form-row form-three">
            <label>
              <span>Limbă</span>

              <input
                v-model="form.languageCode"
                type="text"
                maxlength="2"
                required
                placeholder="ro"
              >
            </label>

            <label>
              <span>Dificultate</span>

              <select v-model="form.difficultyLevel">
                <option
                  v-for="level in 10"
                  :key="`difficulty-${level}`"
                  :value="String(level)"
                >
                  {{ level }}
                </option>
              </select>
            </label>

            <label>
              <span>Complexitate</span>

              <select v-model="form.complexityLevel">
                <option
                  v-for="level in 10"
                  :key="`complexity-${level}`"
                  :value="String(level)"
                >
                  {{ level }}
                </option>
              </select>
            </label>
          </div>

          <fieldset class="teacher-concept-selector">
            <legend>Concept principal</legend>

            <div
              v-if="editorMode === 'edit' && currentConcept"
              class="current-concept"
            >
              <span>Concept actual</span>
              <strong>{{ currentConcept.title }}</strong>
              <small>{{ currentConcept.code }}</small>
            </div>

            <p
              v-if="editorMode === 'edit'"
              class="concept-help"
            >
              Lasă selecția de mai jos goală pentru a păstra
              conceptul actual sau alege unul nou pentru a-l schimba.
            </p>

            <div class="concept-fields">
              <label>
                <span>Nivel</span>

                <select
                  v-model="selectedEducationLevelId"
                  :disabled="catalogLoading"
                  @change="onEducationLevelChange"
                >
                  <option value="">
                    Alege nivelul
                  </option>

                  <option
                    v-for="level in educationLevels"
                    :key="level.id"
                    :value="String(level.id)"
                  >
                    {{ level.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Materie</span>

                <select
                  v-model="selectedCurriculumSubjectId"
                  :disabled="
                    !selectedEducationLevelId ||
                    catalogLoading
                  "
                  @change="onCurriculumSubjectChange"
                >
                  <option value="">
                    Alege materia
                  </option>

                  <option
                    v-for="subject in curriculumSubjects"
                    :key="subject.id"
                    :value="String(subject.id)"
                  >
                    {{ subject.subject.name }}
                  </option>
                </select>
              </label>

              <label>
                <span>Concept</span>

                <select
                  v-model="selectedConceptId"
                  :disabled="
                    !selectedCurriculumSubjectId ||
                    catalogLoading
                  "
                >
                  <option value="">
                    Alege conceptul
                  </option>

                  <option
                    v-for="placement in conceptPlacements"
                    :key="placement.id"
                    :value="
                      String(placement.concept.id)
                    "
                  >
                    {{ placement.concept.title }}
                  </option>
                </select>
              </label>
            </div>
          </fieldset>

          <div
            v-if="editorError"
            class="editor-error"
            role="alert"
          >
            {{ editorError }}
          </div>

          <button
            type="submit"
            class="editor-save"
            :disabled="editorSaving"
          >
            {{
              editorSaving
                ? 'Se salvează...'
                : editorMode === 'create'
                  ? 'Creează draftul'
                  : 'Salvează modificările'
            }}
          </button>
        </form>
      </section>
    </div>
  </section>
</template>

<style scoped>
.teacher-page {
  padding-top: 54px;
}

.teacher-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 30px;
}

.teacher-heading > div {
  max-width: 720px;
}

.teacher-heading h1 {
  margin: 0;
  color: var(--text);
  font-size: 48px;
  letter-spacing: -0.04em;
}

.teacher-heading p {
  margin: 18px 0 0;
  color: var(--muted);
  font-size: 16px;
  line-height: 1.7;
}

.teacher-new-button,
.primary-action,
.editor-save {
  border: 0;
  background: var(--primary);
  color: #fff;
}

.teacher-new-button {
  min-height: 42px;
  padding: 0 16px;
  border-radius: 9px;
  font-weight: 700;
  cursor: pointer;
}

.teacher-new-button:hover,
.primary-action:hover:not(:disabled),
.editor-save:hover:not(:disabled) {
  background: var(--primary-dark);
}

.teacher-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 32px;
}

.teacher-stats > div {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
}

.teacher-stats span,
.teacher-stats strong {
  display: block;
}

.teacher-stats span {
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.teacher-stats strong {
  margin-top: 6px;
  color: var(--text);
  font-size: 24px;
}

.teacher-notice,
.teacher-error {
  margin-top: 18px;
  padding: 13px 15px;
  border-radius: 10px;
  font-size: 12px;
  line-height: 1.5;
}

.teacher-notice {
  background: #ecfdf5;
  color: #047857;
}

.teacher-error {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  background: #fff1f2;
  color: #be123c;
}

.teacher-error button {
  border: 0;
  background: transparent;
  color: inherit;
  font-weight: 800;
  cursor: pointer;
}

.teacher-layout {
  display: grid;
  grid-template-columns:
    minmax(0, 0.9fr)
    minmax(380px, 1.1fr);
  gap: 24px;
  align-items: start;
  margin-top: 28px;
}

.teacher-resources,
.teacher-editor {
  padding: 22px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.teacher-editor {
  position: sticky;
  top: 96px;
}

.teacher-section-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 18px;
}

.teacher-section-heading > div > span {
  display: block;
  margin-bottom: 5px;
  color: var(--primary);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.teacher-section-heading h2 {
  margin: 0;
  color: var(--text);
  font-size: 18px;
}

.teacher-section-heading > span {
  min-width: 29px;
  height: 29px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--background);
  color: var(--muted);
  font-size: 11px;
  font-weight: 800;
}

.teacher-resource-list {
  display: grid;
  gap: 12px;
}

.teacher-resource-card {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 11px;
}

.teacher-resource-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.teacher-resource-type,
.teacher-status {
  padding: 4px 7px;
  border-radius: 6px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.teacher-resource-type {
  background: #eef2ff;
  color: var(--primary);
}

.teacher-status {
  background: var(--background);
  color: var(--muted);
}

.status-draft {
  background: #eff6ff;
  color: #1d4ed8;
}

.status-submitted {
  background: #fffbeb;
  color: #b45309;
}

.status-rejected {
  background: #fff1f2;
  color: #be123c;
}

.status-approved {
  background: #f0fdf4;
  color: #15803d;
}

.status-published {
  background: #ecfdf5;
  color: #047857;
}

.teacher-resource-card h3 {
  margin: 12px 0 0;
  color: var(--text);
  font-size: 15px;
  line-height: 1.4;
}

.teacher-resource-card > p {
  margin: 7px 0 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.6;
}

.teacher-resource-meta,
.teacher-resource-dates {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
  margin-top: 11px;
  color: var(--muted);
  font-size: 9px;
}

.teacher-review-note {
  display: grid;
  gap: 5px;
  margin-top: 12px;
  padding: 10px;
  border-radius: 8px;
  background: #fff7ed;
  color: #9a3412;
  font-size: 10px;
  line-height: 1.5;
}

.teacher-resource-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}

.teacher-resource-actions button,
.editor-cancel {
  min-height: 34px;
  padding: 0 11px;
  border-radius: 7px;
  font: inherit;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
}

.secondary-action,
.editor-cancel {
  border: 1px solid var(--border);
  background: #fff;
  color: var(--text);
}

.primary-action {
  border: 0;
}

.teacher-resource-actions button:disabled {
  opacity: 0.55;
  cursor: wait;
}

.teacher-empty {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 30px 15px;
  border: 1px dashed var(--border);
  border-radius: 10px;
  color: var(--muted);
  font-size: 12px;
  text-align: center;
}

.teacher-empty strong {
  color: var(--text);
}

.teacher-form {
  display: grid;
  gap: 15px;
}

.teacher-form label {
  display: grid;
  gap: 6px;
  min-width: 0;
}

.teacher-form label > span {
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.teacher-form input,
.teacher-form textarea,
.teacher-form select {
  width: 100%;
  min-width: 0;
  padding: 10px 11px;
  border: 1px solid var(--border);
  border-radius: 8px;
  outline: none;
  background: #fff;
  color: var(--text);
  font: inherit;
  font-size: 12px;
}

.teacher-form input,
.teacher-form select {
  min-height: 40px;
}

.teacher-form textarea {
  resize: vertical;
  line-height: 1.6;
}

.teacher-form input:focus,
.teacher-form textarea:focus,
.teacher-form select:focus {
  border-color: var(--primary);
  box-shadow:
    0 0 0 3px rgba(49, 86, 211, 0.1);
}

.teacher-form-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 12px;
}

.teacher-form-row.form-three {
  grid-template-columns: repeat(3, 1fr);
}

.teacher-concept-selector {
  margin: 3px 0 0;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
}

.teacher-concept-selector legend {
  padding: 0 6px;
  color: var(--text);
  font-size: 11px;
  font-weight: 800;
}

.current-concept {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px 9px;
  margin-bottom: 10px;
  padding: 9px 10px;
  border-radius: 8px;
  background: #eef2ff;
}

.current-concept span,
.current-concept small {
  color: var(--muted);
  font-size: 9px;
}

.current-concept strong {
  color: var(--primary);
  font-size: 11px;
}

.concept-help {
  margin: 0 0 11px;
  color: var(--muted);
  font-size: 10px;
  line-height: 1.5;
}

.concept-fields {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 9px;
}

.editor-error {
  padding: 10px 12px;
  border-radius: 8px;
  background: #fff1f2;
  color: #be123c;
  font-size: 11px;
}

.editor-save {
  min-height: 42px;
  border-radius: 8px;
  font-weight: 800;
  cursor: pointer;
}

.editor-save:disabled {
  opacity: 0.6;
  cursor: wait;
}

@media (max-width: 1050px) {
  .teacher-layout {
    grid-template-columns: 1fr;
  }

  .teacher-editor {
    position: static;
  }
}

@media (max-width: 700px) {
  .teacher-heading {
    display: block;
  }

  .teacher-new-button {
    margin-top: 20px;
  }

  .teacher-stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .teacher-form-row,
  .teacher-form-row.form-three,
  .concept-fields {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .teacher-heading h1 {
    font-size: 38px;
  }

  .teacher-stats {
    grid-template-columns: 1fr 1fr;
  }

  .teacher-resources,
  .teacher-editor {
    padding: 17px;
  }
}
</style>
