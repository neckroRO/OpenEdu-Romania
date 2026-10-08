<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue'
import {
  useRoute,
  useRouter,
} from 'vue-router'

import {
  confirmCurriculumMerge,
  getCurriculumMergePreview,
  getCurriculumProposals,
  rejectCurriculumProposal,
} from '../api/curriculum'
import { ApiError } from '../api/client'
import { useAuthStore } from '../stores/auth'

import type {
  CurriculumMergePreview,
  CurriculumProposal,
  CurriculumProposalStatus,
  CurriculumProposalType,
} from '../types/curriculum'

const route = useRoute()
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

const mergePreview =
  ref<CurriculumMergePreview | null>(null)

const mergeLoading = ref(false)
const mergeNote = ref('')

const selected = computed(
  () =>
    proposals.value.find(
      (proposal) =>
        proposal.id === selectedId.value,
    ) ?? null,
)

const mergeProposalId = computed(
  (): number | null => {
    const value = route.query.merge

    if (typeof value !== 'string') {
      return null
    }

    const id = Number(value)

    if (
      !Number.isInteger(id) ||
      id <= 0
    ) {
      return null
    }

    return id
  },
)

const mergeMode = computed(
  () =>
    mergeProposalId.value !== null &&
    selected.value?.id ===
      mergeProposalId.value,
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

async function loadMergePreview(
  proposalId: number,
): Promise<void> {
  mergeLoading.value = true
  mergePreview.value = null
  error.value = null

  try {
    mergePreview.value =
      await getCurriculumMergePreview(
        proposalId,
      )
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
        'Preview-ul de merge nu a putut fi încărcat.',
      )
  } finally {
    mergeLoading.value = false
  }
}

async function closeMergePreview():
Promise<void> {
  mergePreview.value = null
  mergeNote.value = ''

  const query = {
    ...route.query,
  }

  delete query.merge

  await router.replace({
    name: 'curriculum-moderation',
    query,
  })
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

    const requestedMergeId =
      mergeProposalId.value

    if (requestedMergeId !== null) {
      const mergeProposal =
        proposals.value.find(
          (proposal) =>
            proposal.id ===
            requestedMergeId,
        )

      if (
        mergeProposal &&
        mergeProposal.status ===
          'pending' &&
        mergeProposal.proposal_type ===
          'merge_candidate'
      ) {
        selectedId.value =
          mergeProposal.id

        await loadMergePreview(
          mergeProposal.id,
        )
      } else {
        await closeMergePreview()
      }
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
  mergeNote.value = ''
  mergePreview.value = null
  error.value = null
  notice.value = null

  if (mergeProposalId.value !== null) {
    void closeMergePreview()
  }
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

async function openMergePreview():
Promise<void> {
  if (
    !selected.value ||
    selected.value.status !==
      'pending' ||
    selected.value.proposal_type !==
      'merge_candidate'
  ) {
    return
  }

  await router.push({
    name: 'curriculum-moderation',
    query: {
      ...route.query,
      merge:
        String(selected.value.id),
    },
  })

  await loadMergePreview(
    selected.value.id,
  )
}

async function confirmSelectedMerge():
Promise<void> {
  if (
    !selected.value ||
    selected.value.status !==
      'pending' ||
    selected.value.proposal_type !==
      'merge_candidate' ||
    !mergePreview.value ||
    mergePreview.value.blocked
  ) {
    return
  }

  const note = mergeNote.value.trim()

  if (note.length > 2000) {
    error.value =
      'Nota nu poate depăși 2000 de caractere.'

    return
  }

  actionLoading.value = true
  error.value = null
  notice.value = null

  try {
    await confirmCurriculumMerge(
      selected.value.id,
      {
        note: note || null,
      },
    )

    notice.value =
      'Merge-ul curricular a fost confirmat.'

    await closeMergePreview()
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
        'Merge-ul curricular nu a putut fi confirmat.',
      )
  } finally {
    actionLoading.value = false
  }
}

watch(
  mergeProposalId,
  (proposalId) => {
    if (proposalId === null) {
      mergePreview.value = null
      mergeNote.value = ''
    }
  },
)

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

          <section
            v-if="mergeMode"
            class="merge-preview-panel"
          >
            <div class="merge-preview-heading">
              <div>
                <span>
                  PREVIEW FUZIUNE
                </span>

                <h3>
                  Impactul merge-ului
                </h3>
              </div>

              <button
                type="button"
                class="merge-close-button"
                :disabled="
                  actionLoading ||
                  mergeLoading
                "
                @click="closeMergePreview"
              >
                Închide
              </button>
            </div>

            <div
              v-if="mergeLoading"
              class="merge-loading"
            >
              Se calculează impactul merge-ului...
            </div>

            <template
              v-else-if="mergePreview"
            >
              <div class="merge-direction">
                <div>
                  <span>SURSĂ</span>

                  <strong>
                    Materia
                    #{{ mergePreview.source_subject_id }}
                  </strong>
                </div>

                <span class="merge-arrow">
                  →
                </span>

                <div>
                  <span>ȚINTĂ CANONICĂ</span>

                  <strong>
                    Materia
                    #{{ mergePreview.target_subject_id }}
                  </strong>
                </div>
              </div>

              <div class="merge-impact-grid">
                <div>
                  <span>
                    Curriculum mutate
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .curriculum_subjects_to_move
                    }}
                  </strong>
                </div>

                <div
                  :class="{
                    warning:
                      mergePreview
                        .curriculum_subject_collisions >
                      0,
                  }"
                >
                  <span>
                    Coliziuni curriculum
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .curriculum_subject_collisions
                    }}
                  </strong>
                </div>

                <div>
                  <span>
                    Aliasuri mutate
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .aliases_to_move
                    }}
                  </strong>
                </div>

                <div>
                  <span>
                    Aliasuri deduplicate
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .aliases_to_deduplicate
                    }}
                  </strong>
                </div>

                <div>
                  <span>
                    Reputații mutate
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .reputations_to_move
                    }}
                  </strong>
                </div>

                <div>
                  <span>
                    Reputații consolidate
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .reputations_to_consolidate
                    }}
                  </strong>
                </div>

                <div>
                  <span>
                    Evenimente reputație
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .reputation_events_to_move
                    }}
                  </strong>
                </div>

                <div>
                  <span>
                    Alias nume sursă
                  </span>

                  <strong>
                    {{
                      mergePreview
                        .source_name_alias_will_be_created
                        ? 'Da'
                        : 'Nu'
                    }}
                  </strong>
                </div>
              </div>

              <div
                v-if="mergePreview.blocked"
                class="merge-blocked"
                role="alert"
              >
                <strong>
                  Merge blocat
                </strong>

                <span>
                  Backend-ul a identificat probleme
                  care împiedică fuziunea.
                </span>

                <ul>
                  <li
                    v-for="reason in mergePreview.blocking_reasons"
                    :key="reason"
                  >
                    {{ reason }}
                  </li>
                </ul>
              </div>

              <div
                v-else
                class="merge-ready"
              >
                <strong>
                  Merge valid
                </strong>

                <span>
                  Preview-ul nu conține condiții
                  care să blocheze fuziunea.
                </span>
              </div>

              <label class="merge-note-field">
                <span>
                  Notă pentru merge
                </span>

                <textarea
                  v-model="mergeNote"
                  rows="4"
                  maxlength="2000"
                  placeholder="Observații despre fuziunea curriculară..."
                />
              </label>

              <small class="note-counter">
                {{ mergeNote.length }}/2000
              </small>

              <button
                type="button"
                class="confirm-merge-button"
                :disabled="
                  actionLoading ||
                  mergePreview.blocked
                "
                @click="
                  confirmSelectedMerge
                "
              >
                {{
                  actionLoading
                    ? 'Se confirmă...'
                    : mergePreview.blocked
                      ? 'Merge blocat'
                      : 'Confirmă merge-ul'
                }}
              </button>
            </template>
          </section>

          <div
            v-if="
              selected.status ===
                'pending' &&
              !mergeMode
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

.merge-preview-panel {
  margin-top: 22px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.merge-preview-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
}

.merge-preview-heading > div > span {
  display: block;
  margin-bottom: 5px;
  color: var(--primary);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.merge-preview-heading h3 {
  margin: 0;
  color: var(--text);
}

.merge-close-button {
  min-height: 34px;
  padding: 0 11px;
  border: 1px solid var(--border);
  border-radius: 7px;
  background: var(--surface);
  color: var(--text);
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
}

.merge-loading {
  margin-top: 16px;
  padding: 20px;
  border: 1px dashed var(--border);
  border-radius: 9px;
  color: var(--muted);
  font-size: 11px;
  text-align: center;
}

.merge-direction {
  display: grid;
  grid-template-columns:
    minmax(0, 1fr)
    auto
    minmax(0, 1fr);
  gap: 12px;
  align-items: center;
  margin-top: 18px;
}

.merge-direction > div {
  padding: 13px;
  border-radius: 9px;
  background: var(--background);
}

.merge-direction span,
.merge-direction strong {
  display: block;
}

.merge-direction > div > span {
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
}

.merge-direction strong {
  margin-top: 5px;
  color: var(--text);
  font-size: 12px;
}

.merge-arrow {
  color: var(--primary);
  font-size: 20px;
  font-weight: 800;
}

.merge-impact-grid {
  display: grid;
  grid-template-columns:
    repeat(4, minmax(0, 1fr));
  gap: 9px;
  margin-top: 14px;
}

.merge-impact-grid > div {
  padding: 11px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--surface);
}

.merge-impact-grid > div.warning {
  border-color: #fcd34d;
  background: #fffbeb;
}

.merge-impact-grid span,
.merge-impact-grid strong {
  display: block;
}

.merge-impact-grid span {
  color: var(--muted);
  font-size: 8px;
  font-weight: 700;
  text-transform: uppercase;
  line-height: 1.4;
}

.merge-impact-grid strong {
  margin-top: 5px;
  color: var(--text);
  font-size: 18px;
}

.merge-blocked,
.merge-ready {
  display: grid;
  gap: 5px;
  margin-top: 15px;
  padding: 12px;
  border-radius: 9px;
  font-size: 11px;
  line-height: 1.5;
}

.merge-blocked {
  background: #fff1f2;
  color: #be123c;
}

.merge-ready {
  background: #ecfdf5;
  color: #047857;
}

.merge-blocked ul {
  margin: 5px 0 0;
  padding-left: 18px;
}

.merge-note-field {
  display: grid;
  gap: 6px;
  margin-top: 16px;
}

.merge-note-field > span {
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.merge-note-field textarea {
  width: 100%;
  padding: 10px 11px;
  border: 1px solid var(--border);
  border-radius: 8px;
  resize: vertical;
  font: inherit;
  font-size: 12px;
}

.confirm-merge-button {
  width: 100%;
  min-height: 42px;
  margin-top: 12px;
  border: 0;
  border-radius: 8px;
  background: var(--primary);
  color: #fff;
  font-weight: 800;
  cursor: pointer;
}

.confirm-merge-button:disabled,
.merge-close-button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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

  .merge-impact-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }
}

@media (max-width: 520px) {
  .merge-direction {
    grid-template-columns: 1fr;
  }

  .merge-arrow {
    transform: rotate(90deg);
    text-align: center;
  }

  .merge-impact-grid {
    grid-template-columns: 1fr;
  }
}
</style>
