<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'

import { ApiError } from '../api/client'
import {
  approveResourceVersion,
  getModerationQueue,
  publishResourceVersion,
  rejectResourceVersion,
} from '../api/editorial'
import { useAuthStore } from '../stores/auth'

import type {
  ModerationQueueItem,
  ModerationStatus,
} from '../types/moderation'

const router = useRouter()
const authStore = useAuthStore()

const queue = ref<ModerationQueueItem[]>([])
const selectedId = ref<number | null>(null)

const loading = ref(false)
const actionLoading = ref(false)

const error = ref<string | null>(null)
const notice = ref<string | null>(null)
const rejectNote = ref('')

const selected = computed(
  () =>
    queue.value.find(
      (item) => item.id === selectedId.value,
    ) ?? null,
)

const submittedCount = computed(
  () =>
    queue.value.filter(
      (item) => item.status === 'submitted',
    ).length,
)

const approvedCount = computed(
  () =>
    queue.value.filter(
      (item) => item.status === 'approved',
    ).length,
)

function statusLabel(
  status: ModerationStatus,
): string {
  return status === 'submitted'
    ? 'De revizuit'
    : 'Aprobată'
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
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function requestErrorMessage(
  caught: unknown,
  fallback: string,
): string {
  if (caught instanceof ApiError) {
    if (caught.status === 403) {
      return 'Nu ai permisiunea necesară pentru această acțiune.'
    }

    if (caught.status === 409) {
      return 'Starea resursei s-a modificat. Reîncarcă lista.'
    }

    if (caught.status === 422) {
      return 'Verifică datele introduse.'
    }
  }

  return fallback
}

async function handleExpiredSession(
  caught: unknown,
): Promise<boolean> {
  if (
    caught instanceof ApiError &&
    caught.status === 401
  ) {
    await authStore.logout()

    await router.push({
      name: 'login',
      query: {
        redirect: '/moderation',
      },
    })

    return true
  }

  return false
}

async function loadQueue(): Promise<void> {
  const previousSelection = selectedId.value

  loading.value = true
  error.value = null

  try {
    queue.value = await getModerationQueue()

    if (
      previousSelection &&
      queue.value.some(
        (item) => item.id === previousSelection,
      )
    ) {
      selectedId.value = previousSelection
    } else {
      selectedId.value =
        queue.value[0]?.id ?? null
    }
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    queue.value = []
    selectedId.value = null

    error.value = requestErrorMessage(
      caught,
      'Coada de moderare nu a putut fi încărcată.',
    )
  } finally {
    loading.value = false
  }
}

function selectItem(item: ModerationQueueItem): void {
  selectedId.value = item.id
  rejectNote.value = ''
  error.value = null
  notice.value = null
}

async function approve(): Promise<void> {
  if (
    !selected.value ||
    selected.value.status !== 'submitted'
  ) {
    return
  }

  actionLoading.value = true
  error.value = null
  notice.value = null

  try {
    await approveResourceVersion(
      selected.value.id,
    )

    notice.value =
      'Resursa a fost aprobată și este pregătită pentru publicare.'

    await loadQueue()
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    error.value = requestErrorMessage(
      caught,
      'Resursa nu a putut fi aprobată.',
    )
  } finally {
    actionLoading.value = false
  }
}

async function reject(): Promise<void> {
  if (
    !selected.value ||
    selected.value.status !== 'submitted'
  ) {
    return
  }

  const note = rejectNote.value.trim()

  if (!note) {
    error.value =
      'Feedback-ul este obligatoriu pentru respingere.'
    return
  }

  if (note.length > 5000) {
    error.value =
      'Feedback-ul nu poate depăși 5000 de caractere.'
    return
  }

  actionLoading.value = true
  error.value = null
  notice.value = null

  try {
    await rejectResourceVersion(
      selected.value.id,
      note,
    )

    rejectNote.value = ''

    notice.value =
      'Resursa a fost returnată autorului cu feedback.'

    await loadQueue()
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    error.value = requestErrorMessage(
      caught,
      'Resursa nu a putut fi respinsă.',
    )
  } finally {
    actionLoading.value = false
  }
}

async function publish(): Promise<void> {
  if (
    !selected.value ||
    selected.value.status !== 'approved'
  ) {
    return
  }

  actionLoading.value = true
  error.value = null
  notice.value = null

  try {
    await publishResourceVersion(
      selected.value.id,
    )

    notice.value =
      'Resursa a fost publicată în catalogul OpenEdu.'

    await loadQueue()
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    error.value = requestErrorMessage(
      caught,
      'Resursa nu a putut fi publicată.',
    )
  } finally {
    actionLoading.value = false
  }
}

onMounted(() => {
  void loadQueue()
})
</script>

<template>
  <section class="page moderation-page">
    <header class="moderation-heading">
      <div>
        <span class="eyebrow">MODERARE</span>

        <h1>Revizuire editorială</h1>

        <p>
          Verifică materialele trimise de contributori,
          oferă feedback și publică resursele validate.
        </p>
      </div>

      <button
        type="button"
        class="refresh-button"
        :disabled="loading"
        @click="loadQueue"
      >
        {{ loading ? 'Se actualizează...' : 'Actualizează' }}
      </button>
    </header>

    <div class="moderation-stats">
      <div>
        <span>În coadă</span>
        <strong>{{ queue.length }}</strong>
      </div>

      <div>
        <span>De revizuit</span>
        <strong>{{ submittedCount }}</strong>
      </div>

      <div>
        <span>De publicat</span>
        <strong>{{ approvedCount }}</strong>
      </div>
    </div>

    <div
      v-if="notice"
      class="moderation-notice"
      role="status"
    >
      {{ notice }}
    </div>

    <div
      v-if="error"
      class="moderation-error"
      role="alert"
    >
      {{ error }}
    </div>

    <div class="moderation-layout">
      <section class="moderation-queue">
        <div class="section-heading">
          <div>
            <span>COADĂ EDITORIALĂ</span>
            <h2>Materiale în lucru</h2>
          </div>

          <span>{{ queue.length }}</span>
        </div>

        <div
          v-if="loading && queue.length === 0"
          class="empty-state"
        >
          Se încarcă materialele...
        </div>

        <div
          v-else-if="queue.length === 0"
          class="empty-state"
        >
          <strong>Coada este goală.</strong>
          <span>
            Nu există materiale care necesită intervenția
            unui moderator.
          </span>
        </div>

        <div
          v-else
          class="queue-list"
        >
          <button
            v-for="item in queue"
            :key="item.id"
            type="button"
            class="queue-card"
            :class="{
              selected: item.id === selectedId,
            }"
            @click="selectItem(item)"
          >
            <div class="queue-card-top">
              <span class="resource-type">
                {{ item.resource.type }}
              </span>

              <span
                class="moderation-status"
                :class="`status-${item.status}`"
              >
                {{ statusLabel(item.status) }}
              </span>
            </div>

            <strong>{{ item.title }}</strong>

            <span class="queue-summary">
              {{
                item.summary ||
                'Fără rezumat.'
              }}
            </span>

            <div class="queue-meta">
              <span>
                {{
                  item.creator?.name ||
                  'Autor necunoscut'
                }}
              </span>

              <span v-if="item.concept">
                {{ item.concept.title }}
              </span>

              <span>
                v{{ item.version_number }}
              </span>
            </div>
          </button>
        </div>
      </section>

      <section class="moderation-preview">
        <template v-if="selected">
          <div class="preview-header">
            <div>
              <span class="preview-code">
                {{ selected.resource.code }}
              </span>

              <h2>{{ selected.title }}</h2>
            </div>

            <span
              class="moderation-status preview-status"
              :class="`status-${selected.status}`"
            >
              {{ statusLabel(selected.status) }}
            </span>
          </div>

          <div class="preview-metadata">
            <div>
              <span>Autor</span>
              <strong>
                {{
                  selected.creator?.name ||
                  'Necunoscut'
                }}
              </strong>
              <small>
                {{ selected.creator?.email || '—' }}
              </small>
            </div>

            <div>
              <span>Concept</span>
              <strong>
                {{
                  selected.concept?.title ||
                  'Nespecificat'
                }}
              </strong>
              <small>
                {{ selected.concept?.code || '—' }}
              </small>
            </div>

            <div>
              <span>Limbă</span>
              <strong>
                {{ selected.language_code.toUpperCase() }}
              </strong>
            </div>

            <div>
              <span>Dificultate</span>
              <strong>
                {{ selected.difficulty_level }}/10
              </strong>
            </div>

            <div>
              <span>Complexitate</span>
              <strong>
                {{ selected.complexity_level }}/10
              </strong>
            </div>

            <div>
              <span>Trimisă</span>
              <strong>
                {{ formatDate(selected.submitted_at) }}
              </strong>
            </div>
          </div>

          <div
            v-if="selected.summary"
            class="preview-block"
          >
            <span>REZUMAT</span>
            <p>{{ selected.summary }}</p>
          </div>

          <div class="preview-block">
            <span>CONȚINUT</span>

            <pre v-if="selected.content">{{ selected.content }}</pre>

            <p
              v-else
              class="empty-content"
            >
              Resursa nu conține text.
            </p>
          </div>

          <div
            v-if="selected.source_url"
            class="preview-source"
          >
            <span>Sursă externă</span>

            <a
              :href="selected.source_url"
              target="_blank"
              rel="noopener noreferrer"
            >
              Deschide sursa ↗
            </a>
          </div>

          <div
            v-if="selected.status === 'submitted'"
            class="review-panel"
          >
            <div class="review-heading">
              <span>DECIZIE EDITORIALĂ</span>
              <h3>Review</h3>
            </div>

            <label>
              <span>
                Feedback pentru autor
              </span>

              <textarea
                v-model="rejectNote"
                rows="5"
                maxlength="5000"
                placeholder="Completează feedback-ul dacă materialul trebuie revizuit..."
              />
            </label>

            <small class="feedback-counter">
              {{ rejectNote.length }}/5000
            </small>

            <div class="review-actions">
              <button
                type="button"
                class="reject-button"
                :disabled="actionLoading"
                @click="reject"
              >
                Respinge cu feedback
              </button>

              <button
                type="button"
                class="approve-button"
                :disabled="actionLoading"
                @click="approve"
              >
                {{
                  actionLoading
                    ? 'Se procesează...'
                    : 'Aprobă resursa'
                }}
              </button>
            </div>
          </div>

          <div
            v-else-if="selected.status === 'approved'"
            class="publish-panel"
          >
            <div>
              <span>PREGĂTITĂ PENTRU PUBLICARE</span>
              <strong>
                Resursa a trecut procesul de review.
              </strong>
            </div>

            <button
              type="button"
              class="publish-button"
              :disabled="actionLoading"
              @click="publish"
            >
              {{
                actionLoading
                  ? 'Se publică...'
                  : 'Publică în OpenEdu'
              }}
            </button>
          </div>
        </template>

        <div
          v-else
          class="preview-empty"
        >
          <strong>Selectează o resursă</strong>
          <span>
            Detaliile materialului vor apărea aici.
          </span>
        </div>
      </section>
    </div>
  </section>
</template>

<style scoped>
.moderation-page {
  padding-top: 54px;
}

.moderation-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 30px;
}

.moderation-heading > div {
  max-width: 720px;
}

.moderation-heading h1 {
  margin: 0;
  color: var(--text);
  font-size: 48px;
  letter-spacing: -0.04em;
}

.moderation-heading p {
  margin: 18px 0 0;
  color: var(--muted);
  font-size: 16px;
  line-height: 1.7;
}

.refresh-button {
  min-height: 42px;
  padding: 0 16px;
  border: 1px solid var(--border);
  border-radius: 9px;
  background: white;
  color: var(--text);
  font-weight: 700;
  cursor: pointer;
}

.refresh-button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.moderation-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-top: 32px;
}

.moderation-stats > div {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: white;
}

.moderation-stats span,
.moderation-stats strong {
  display: block;
}

.moderation-stats span {
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
}

.moderation-stats strong {
  margin-top: 6px;
  font-size: 25px;
}

.moderation-notice,
.moderation-error {
  margin-top: 18px;
  padding: 13px 15px;
  border-radius: 9px;
  font-size: 12px;
}

.moderation-notice {
  background: #ecfdf5;
  color: #047857;
}

.moderation-error {
  background: #fff1f2;
  color: #be123c;
}

.moderation-layout {
  display: grid;
  grid-template-columns:
    minmax(280px, 0.72fr)
    minmax(0, 1.28fr);
  gap: 22px;
  align-items: start;
  margin-top: 26px;
}

.moderation-queue,
.moderation-preview {
  border: 1px solid var(--border);
  border-radius: 14px;
  background: white;
}

.moderation-queue {
  padding: 20px;
}

.moderation-preview {
  min-height: 500px;
  padding: 26px;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 17px;
}

.section-heading > div > span,
.review-heading > span,
.publish-panel > div > span {
  display: block;
  margin-bottom: 5px;
  color: var(--primary);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.section-heading h2,
.review-heading h3 {
  margin: 0;
  font-size: 18px;
}

.section-heading > span {
  min-width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--background);
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
}

.queue-list {
  display: grid;
  gap: 9px;
}

.queue-card {
  width: 100%;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: white;
  color: inherit;
  text-align: left;
  cursor: pointer;
}

.queue-card:hover,
.queue-card.selected {
  border-color: #bfcaf4;
  background: #f8f9ff;
}

.queue-card.selected {
  box-shadow:
    inset 3px 0 0 var(--primary);
}

.queue-card-top {
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.resource-type,
.moderation-status {
  padding: 4px 7px;
  border-radius: 6px;
  font-size: 8px;
  font-weight: 800;
  text-transform: uppercase;
}

.resource-type {
  background: #eef2ff;
  color: var(--primary);
}

.status-submitted {
  background: #fffbeb;
  color: #b45309;
}

.status-approved {
  background: #ecfdf5;
  color: #047857;
}

.queue-card > strong {
  display: block;
  margin-top: 11px;
  font-size: 13px;
  line-height: 1.45;
}

.queue-summary {
  display: block;
  margin-top: 5px;
  color: var(--muted);
  font-size: 10px;
  line-height: 1.5;
}

.queue-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 5px 9px;
  margin-top: 10px;
  color: var(--muted);
  font-size: 8px;
}

.empty-state,
.preview-empty {
  display: flex;
  flex-direction: column;
  gap: 6px;
  color: var(--muted);
  text-align: center;
}

.empty-state {
  padding: 32px 15px;
  border: 1px dashed var(--border);
  border-radius: 9px;
  font-size: 11px;
}

.preview-empty {
  min-height: 430px;
  align-items: center;
  justify-content: center;
}

.empty-state strong,
.preview-empty strong {
  color: var(--text);
}

.preview-header {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.preview-code {
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.preview-header h2 {
  margin: 6px 0 0;
  font-size: 27px;
  letter-spacing: -0.025em;
}

.preview-status {
  height: fit-content;
  white-space: nowrap;
}

.preview-metadata {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-top: 22px;
}

.preview-metadata > div {
  padding: 11px;
  border-radius: 8px;
  background: var(--background);
}

.preview-metadata span,
.preview-metadata strong,
.preview-metadata small {
  display: block;
}

.preview-metadata span {
  color: var(--muted);
  font-size: 8px;
  font-weight: 800;
  text-transform: uppercase;
}

.preview-metadata strong {
  margin-top: 4px;
  font-size: 11px;
}

.preview-metadata small {
  margin-top: 3px;
  color: var(--muted);
  font-size: 8px;
}

.preview-block {
  margin-top: 24px;
}

.preview-block > span,
.preview-source > span {
  display: block;
  margin-bottom: 8px;
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.07em;
}

.preview-block p {
  margin: 0;
  font-size: 12px;
  line-height: 1.75;
}

.preview-block pre {
  margin: 0;
  padding: 17px;
  overflow-x: auto;
  border: 1px solid var(--border);
  border-radius: 9px;
  background: #fbfcfe;
  color: var(--text);
  font-family: inherit;
  font-size: 12px;
  line-height: 1.75;
  white-space: pre-wrap;
  word-break: break-word;
}

.empty-content {
  color: var(--muted);
}

.preview-source {
  margin-top: 20px;
}

.preview-source a {
  color: var(--primary);
  font-size: 11px;
  font-weight: 800;
}

.review-panel,
.publish-panel {
  margin-top: 26px;
  padding-top: 22px;
  border-top: 1px solid var(--border);
}

.review-panel label {
  display: grid;
  gap: 6px;
  margin-top: 15px;
}

.review-panel label > span {
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.review-panel textarea {
  width: 100%;
  padding: 11px;
  border: 1px solid var(--border);
  border-radius: 8px;
  outline: none;
  resize: vertical;
  font: inherit;
  font-size: 11px;
  line-height: 1.6;
}

.review-panel textarea:focus {
  border-color: var(--primary);
  box-shadow:
    0 0 0 3px rgba(49, 86, 211, 0.1);
}

.feedback-counter {
  display: block;
  margin-top: 5px;
  color: var(--muted);
  font-size: 8px;
  text-align: right;
}

.review-actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 14px;
}

.review-actions button,
.publish-button {
  min-height: 38px;
  padding: 0 14px;
  border-radius: 8px;
  font-weight: 800;
  cursor: pointer;
}

.reject-button {
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.approve-button,
.publish-button {
  border: 0;
  background: var(--primary);
  color: white;
}

.approve-button:hover:not(:disabled),
.publish-button:hover:not(:disabled) {
  background: var(--primary-dark);
}

.review-actions button:disabled,
.publish-button:disabled {
  opacity: 0.55;
  cursor: wait;
}

.publish-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.publish-panel strong {
  display: block;
  font-size: 12px;
}

@media (max-width: 1050px) {
  .moderation-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 700px) {
  .moderation-heading {
    display: block;
  }

  .refresh-button {
    margin-top: 20px;
  }

  .moderation-stats {
    grid-template-columns: 1fr;
  }

  .preview-metadata {
    grid-template-columns: repeat(2, 1fr);
  }

  .publish-panel {
    align-items: stretch;
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .moderation-heading h1 {
    font-size: 38px;
  }

  .preview-header {
    flex-direction: column;
  }

  .preview-metadata {
    grid-template-columns: 1fr;
  }

  .review-actions {
    flex-direction: column-reverse;
  }

  .review-actions button {
    width: 100%;
  }
}
</style>
