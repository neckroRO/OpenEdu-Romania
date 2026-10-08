<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'

import {
  getCurriculumProposals,
  rejectCurriculumProposal,
} from '../api/curriculum'
import { ApiError } from '../api/client'
import { useAuthStore } from '../stores/auth'

import type {
  CurriculumProposal,
  CurriculumProposalStatus,
  CurriculumProposalType,
} from '../types/curriculum'

const router = useRouter()
const authStore = useAuthStore()

const proposals = ref<CurriculumProposal[]>([])
const selectedId = ref<number | null>(null)

const statusFilter = ref<'all' | CurriculumProposalStatus>('all')
const typeFilter = ref<'all' | CurriculumProposalType>('all')

const loading = ref(false)
const actionLoading = ref(false)

const error = ref<string | null>(null)
const notice = ref<string | null>(null)
const rejectNote = ref('')

const selected = computed(
  () =>
    proposals.value.find(
      (proposal) =>
        proposal.id === selectedId.value,
    ) ?? null,
)

const filteredProposals = computed(() =>
  proposals.value.filter((proposal) => {
    if (
      statusFilter.value !== 'all' &&
      proposal.status !== statusFilter.value
    ) {
      return false
    }

    if (
      typeFilter.value !== 'all' &&
      proposal.proposal_type !== typeFilter.value
    ) {
      return false
    }

    return true
  }),
)

const pendingCount = computed(
  () =>
    proposals.value.filter(
      (proposal) =>
        proposal.status === 'pending',
    ).length,
)

const rejectedCount = computed(
  () =>
    proposals.value.filter(
      (proposal) =>
        proposal.status === 'rejected',
    ).length,
)

const mergedCount = computed(
  () =>
    proposals.value.filter(
      (proposal) =>
        proposal.status === 'merged',
    ).length,
)

function statusLabel(
  status: CurriculumProposalStatus,
): string {
  const labels: Record<
    CurriculumProposalStatus,
    string
  > = {
    pending: 'În așteptare',
    approved: 'Aprobată',
    rejected: 'Respinsă',
    merged: 'Fuzionată',
  }

  return labels[status]
}

function typeLabel(
  type: CurriculumProposalType,
): string {
  const labels: Record<
    CurriculumProposalType,
    string
  > = {
    create: 'Materie nouă',
    update: 'Actualizare',
    alias: 'Alias',
    merge_candidate: 'Posibil duplicat',
  }

  return labels[type]
}

function proposalTitle(
  proposal: CurriculumProposal,
): string {
  const name = proposal.payload.name

  if (
    typeof name === 'string' &&
    name.trim()
  ) {
    return name
  }

  if (proposal.entity_id !== null) {
    return `Materia #${proposal.entity_id}`
  }

  return `Propunerea #${proposal.id}`
}

function formatDate(
  value: string | null,
): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return new Intl.DateTimeFormat(
    'ro-RO',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    },
  ).format(date)
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
      return 'Starea propunerii s-a modificat. Reîncarcă lista.'
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
        redirect:
          '/curriculum/moderation',
      },
    })

    return true
  }

  return false
}

async function loadProposals():
Promise<void> {
  const previousSelection =
    selectedId.value

  loading.value = true
  error.value = null

  try {
    proposals.value =
      await getCurriculumProposals()

    if (
      previousSelection &&
      proposals.value.some(
        (proposal) =>
          proposal.id ===
          previousSelection,
      )
    ) {
      selectedId.value =
        previousSelection
    } else {
      selectedId.value =
        proposals.value[0]?.id ?? null
    }
  } catch (caught) {
    if (
      await handleExpiredSession(
        caught,
      )
    ) {
      return
    }

    proposals.value = []
    selectedId.value = null

    error.value =
      requestErrorMessage(
        caught,
        'Propunerile curriculare nu au putut fi încărcate.',
      )
  } finally {
    loading.value = false
  }
}

function selectProposal(
  proposal: CurriculumProposal,
): void {
  selectedId.value = proposal.id
  rejectNote.value = ''
  error.value = null
  notice.value = null
}

async function rejectSelected():
Promise<void> {
  if (
    !selected.value ||
    selected.value.status !== 'pending'
  ) {
    return
  }

  const note =
    rejectNote.value.trim()

  if (note.length > 2000) {
    error.value =
      'Nota nu poate depăși 2000 de caractere.'

    return
  }

  actionLoading.value = true
  error.value = null
  notice.value = null

  try {
    await rejectCurriculumProposal(
      selected.value.id,
      {
        note: note || null,
      },
    )

    rejectNote.value = ''

    notice.value =
      'Propunerea a fost respinsă.'

    await loadProposals()
  } catch (caught) {
    if (
      await handleExpiredSession(
        caught,
      )
    ) {
      return
    }

    error.value =
      requestErrorMessage(
        caught,
        'Propunerea nu a putut fi respinsă.',
      )
  } finally {
    actionLoading.value = false
  }
}

function openMergePreview():
void {
  if (
    !selected.value ||
    selected.value.proposal_type !==
      'merge_candidate'
  ) {
    return
  }

  void router.push({
    name: 'curriculum-moderation',
    query: {
      merge:
        String(selected.value.id),
    },
  })
}

onMounted(() => {
  void loadProposals()
})
</script>

<template>
  <section class="page curriculum-moderation-page">
    <header class="moderation-heading">
      <div>
        <span class="eyebrow">
          MODERARE CURRICULUM
        </span>

        <h1>Propuneri curriculare</h1>

        <p>
          Verifică propunerile comunității,
          identifică duplicatele și decide
          ce modificări pot continua.
        </p>
      </div>

      <button
        type="button"
        class="refresh-button"
        :disabled="loading"
        @click="loadProposals"
      >
        {{
          loading
            ? 'Se actualizează...'
            : 'Actualizează'
        }}
      </button>
    </header>

    <div class="moderation-stats">
      <div>
        <span>Total</span>
        <strong>{{ proposals.length }}</strong>
      </div>

      <div>
        <span>De revizuit</span>
        <strong>{{ pendingCount }}</strong>
      </div>

      <div>
        <span>Respinse</span>
        <strong>{{ rejectedCount }}</strong>
      </div>

      <div>
        <span>Fuzionate</span>
        <strong>{{ mergedCount }}</strong>
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

    <div class="filters">
      <label>
        <span>Status</span>

        <select v-model="statusFilter">
          <option value="all">
            Toate
          </option>

          <option value="pending">
            În așteptare
          </option>

          <option value="approved">
            Aprobate
          </option>

          <option value="rejected">
            Respinse
          </option>

          <option value="merged">
            Fuzionate
          </option>
        </select>
      </label>

      <label>
        <span>Tip</span>

        <select v-model="typeFilter">
          <option value="all">
            Toate
          </option>

          <option value="create">
            Materie nouă
          </option>

          <option value="update">
            Actualizare
          </option>

          <option value="alias">
            Alias
          </option>

          <option value="merge_candidate">
            Posibil duplicat
          </option>
        </select>
      </label>
    </div>

    <div class="moderation-layout">
      <section class="proposal-queue">
        <div class="section-heading">
          <div>
            <span>COADĂ CURRICULARĂ</span>
            <h2>Propuneri</h2>
          </div>

          <span>
            {{ filteredProposals.length }}
          </span>
        </div>

        <div
          v-if="
            loading &&
            proposals.length === 0
          "
          class="empty-state"
        >
          Se încarcă propunerile...
        </div>

        <div
          v-else-if="
            filteredProposals.length === 0
          "
          class="empty-state"
        >
          <strong>
            Nu există rezultate.
          </strong>

          <span>
            Schimbă filtrele sau actualizează lista.
          </span>
        </div>

        <div
          v-else
          class="proposal-list"
        >
          <button
            v-for="proposal in filteredProposals"
            :key="proposal.id"
            type="button"
            class="proposal-card"
            :class="{
              selected:
                proposal.id === selectedId,
            }"
            @click="
              selectProposal(proposal)
            "
          >
            <div class="proposal-card-top">
              <span class="proposal-type">
                {{
                  typeLabel(
                    proposal.proposal_type,
                  )
                }}
              </span>

              <span
                class="proposal-status"
                :class="
                  `status-${proposal.status}`
                "
              >
                {{
                  statusLabel(
                    proposal.status,
                  )
                }}
              </span>
            </div>

            <strong>
              {{ proposalTitle(proposal) }}
            </strong>

            <span class="proposal-summary">
              {{
                proposal.reason ||
                'Fără motiv specificat.'
              }}
            </span>

            <div class="proposal-meta">
              <span>
                #{{ proposal.id }}
              </span>

              <span>
                Utilizator
                #{{ proposal.proposed_by }}
              </span>
            </div>
          </button>
        </div>
      </section>

      <section class="proposal-preview">
        <template v-if="selected">
          <div class="preview-header">
            <div>
              <span class="preview-code">
                PROPUNERE
                #{{ selected.id }}
              </span>

              <h2>
                {{
                  proposalTitle(
                    selected,
                  )
                }}
              </h2>
            </div>

            <span
              class="proposal-status"
              :class="
                `status-${selected.status}`
              "
            >
              {{
                statusLabel(
                  selected.status,
                )
              }}
            </span>
          </div>

          <div class="preview-metadata">
            <div>
              <span>Tip</span>
              <strong>
                {{
                  typeLabel(
                    selected.proposal_type,
                  )
                }}
              </strong>
            </div>

            <div>
              <span>Entitate</span>
              <strong>
                {{ selected.entity_type }}
              </strong>
            </div>

            <div>
              <span>ID entitate</span>
              <strong>
                {{
                  selected.entity_id ??
                  'Nouă'
                }}
              </strong>
            </div>

            <div>
              <span>Autor</span>
              <strong>
                #{{ selected.proposed_by }}
              </strong>
            </div>

            <div>
              <span>Creată</span>
              <strong>
                {{
                  formatDate(
                    selected.created_at,
                  )
                }}
              </strong>
            </div>

            <div>
              <span>Revizuită</span>
              <strong>
                {{
                  formatDate(
                    selected.reviewed_at,
                  )
                }}
              </strong>
            </div>
          </div>

          <div class="preview-block">
            <span>DATE PROPUSE</span>

            <dl>
              <template
                v-for="(
                  value,
                  key
                ) in selected.payload"
                :key="String(key)"
              >
                <dt>{{ key }}</dt>

                <dd>
                  {{
                    typeof value ===
                    'object'
                      ? JSON.stringify(value)
                      : value
                  }}
                </dd>
              </template>
            </dl>
          </div>

          <div
            v-if="selected.reason"
            class="preview-block"
          >
            <span>MOTIV</span>

            <p>
              {{ selected.reason }}
            </p>
          </div>

          <div
            v-if="selected.review_note"
            class="review-note"
          >
            <strong>
              Notă moderator
            </strong>

            <span>
              {{ selected.review_note }}
            </span>
          </div>

          <div
            v-if="
              selected.status ===
              'pending'
            "
            class="review-panel"
          >
            <div class="review-heading">
              <span>
                DECIZIE CURRICULARĂ
              </span>

              <h3>Moderare</h3>
            </div>

            <label>
              <span>
                Notă pentru contributor
              </span>

              <textarea
                v-model="rejectNote"
                rows="5"
                maxlength="2000"
                placeholder="Motivul respingerii sau observații..."
              />
            </label>

            <small class="note-counter">
              {{ rejectNote.length }}/2000
            </small>

            <div class="review-actions">
              <button
                type="button"
                class="reject-button"
                :disabled="actionLoading"
                @click="rejectSelected"
              >
                {{
                  actionLoading
                    ? 'Se procesează...'
                    : 'Respinge propunerea'
                }}
              </button>

              <button
                v-if="
                  selected.proposal_type ===
                  'merge_candidate'
                "
                type="button"
                class="preview-button"
                :disabled="actionLoading"
                @click="openMergePreview"
              >
                Vezi preview merge
              </button>
            </div>
          </div>
        </template>

        <div
          v-else
          class="preview-empty"
        >
          <strong>
            Selectează o propunere
          </strong>

          <span>
            Detaliile vor apărea aici.
          </span>
        </div>
      </section>
    </div>
  </section>
</template>

<style scoped>
.curriculum-moderation-page {
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
  background: var(--surface);
  color: var(--text);
  font-weight: 700;
  cursor: pointer;
}

.moderation-stats {
  display: grid;
  grid-template-columns:
    repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 32px;
}

.moderation-stats > div {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
}

.moderation-stats span,
.moderation-stats strong {
  display: block;
}

.moderation-stats span {
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.moderation-stats strong {
  margin-top: 6px;
  color: var(--text);
  font-size: 24px;
}

.moderation-notice,
.moderation-error {
  margin-top: 18px;
  padding: 13px 15px;
  border-radius: 10px;
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

.filters {
  display: flex;
  gap: 12px;
  margin-top: 22px;
}

.filters label {
  display: grid;
  gap: 5px;
}

.filters span {
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.filters select {
  min-width: 170px;
  min-height: 38px;
  padding: 0 10px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--surface);
  color: var(--text);
}

.moderation-layout {
  display: grid;
  grid-template-columns:
    minmax(320px, 0.8fr)
    minmax(0, 1.2fr);
  gap: 24px;
  margin-top: 18px;
  align-items: start;
}

.proposal-queue,
.proposal-preview {
  padding: 22px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.proposal-preview {
  position: sticky;
  top: 96px;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 18px;
}

.section-heading > div > span,
.review-heading > span,
.preview-block > span {
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
  color: var(--text);
}

.section-heading > span {
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

.proposal-list {
  display: grid;
  gap: 10px;
}

.proposal-card {
  width: 100%;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--surface);
  text-align: left;
  cursor: pointer;
}

.proposal-card.selected {
  border-color: var(--primary);
  box-shadow:
    0 0 0 2px
    rgba(49, 86, 211, 0.08);
}

.proposal-card-top,
.preview-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.proposal-type,
.proposal-status {
  padding: 4px 7px;
  border-radius: 6px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.proposal-type {
  background: #eef2ff;
  color: var(--primary);
}

.status-pending {
  background: #fffbeb;
  color: #b45309;
}

.status-approved,
.status-merged {
  background: #ecfdf5;
  color: #047857;
}

.status-rejected {
  background: #fff1f2;
  color: #be123c;
}

.proposal-card > strong {
  display: block;
  margin-top: 10px;
  color: var(--text);
  font-size: 14px;
}

.proposal-summary {
  display: block;
  margin-top: 6px;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.5;
}

.proposal-meta {
  display: flex;
  gap: 10px;
  margin-top: 9px;
  color: var(--muted);
  font-size: 9px;
}

.preview-code {
  color: var(--primary);
  font-size: 9px;
  font-weight: 800;
}

.preview-header h2 {
  margin: 5px 0 0;
  color: var(--text);
}

.preview-metadata {
  display: grid;
  grid-template-columns:
    repeat(3, 1fr);
  gap: 10px;
  margin-top: 20px;
}

.preview-metadata > div {
  padding: 10px;
  border-radius: 8px;
  background: var(--background);
}

.preview-metadata span,
.preview-metadata strong {
  display: block;
}

.preview-metadata span {
  color: var(--muted);
  font-size: 9px;
  text-transform: uppercase;
}

.preview-metadata strong {
  margin-top: 4px;
  color: var(--text);
  font-size: 11px;
}

.preview-block {
  margin-top: 20px;
}

.preview-block p {
  color: var(--text);
  font-size: 12px;
  line-height: 1.6;
}

.preview-block dl {
  display: grid;
  grid-template-columns:
    minmax(120px, 0.4fr)
    1fr;
  gap: 8px 12px;
  margin: 10px 0 0;
  font-size: 11px;
}

.preview-block dt {
  color: var(--muted);
  font-weight: 700;
}

.preview-block dd {
  margin: 0;
  color: var(--text);
  overflow-wrap: anywhere;
}

.review-note {
  display: grid;
  gap: 5px;
  margin-top: 18px;
  padding: 11px;
  border-radius: 8px;
  background: #fff7ed;
  color: #9a3412;
  font-size: 11px;
}

.review-panel {
  margin-top: 22px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.review-panel label {
  display: grid;
  gap: 6px;
  margin-top: 15px;
}

.review-panel label > span {
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.review-panel textarea {
  width: 100%;
  padding: 10px 11px;
  border: 1px solid var(--border);
  border-radius: 8px;
  resize: vertical;
  font: inherit;
  font-size: 12px;
}

.note-counter {
  display: block;
  margin-top: 5px;
  color: var(--muted);
  font-size: 9px;
  text-align: right;
}

.review-actions {
  display: flex;
  gap: 9px;
  margin-top: 14px;
}

.reject-button,
.preview-button {
  min-height: 38px;
  padding: 0 13px;
  border-radius: 8px;
  font-weight: 800;
  cursor: pointer;
}

.reject-button {
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.preview-button {
  border: 0;
  background: var(--primary);
  color: #fff;
}

.empty-state,
.preview-empty {
  display: grid;
  gap: 6px;
  padding: 30px 15px;
  border: 1px dashed var(--border);
  border-radius: 10px;
  color: var(--muted);
  font-size: 12px;
  text-align: center;
}

.empty-state strong,
.preview-empty strong {
  color: var(--text);
}

@media (max-width: 1050px) {
  .moderation-layout {
    grid-template-columns: 1fr;
  }

  .proposal-preview {
    position: static;
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
    grid-template-columns:
      repeat(2, 1fr);
  }

  .filters {
    display: grid;
  }

  .preview-metadata {
    grid-template-columns:
      repeat(2, 1fr);
  }
}
</style>
