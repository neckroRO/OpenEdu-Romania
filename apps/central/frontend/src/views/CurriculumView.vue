<script setup lang="ts">
import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'

import {
  createCurriculumProposal,
  getCurriculumProposals,
} from '../api/curriculum'
import { ApiError } from '../api/client'
import { useAuthStore } from '../stores/auth'

import type {
  CurriculumProposal,
  CurriculumProposalStatus,
} from '../types/curriculum'

const router = useRouter()
const authStore = useAuthStore()

const proposals = ref<CurriculumProposal[]>([])

const loading = ref(false)
const saving = ref(false)

const loadError = ref<string | null>(null)
const formError = ref<string | null>(null)
const notice = ref<string | null>(null)

const form = reactive({
  name: '',
  code: '',
  description: '',
  reason: '',
})

const stats = computed(() => ({
  total: proposals.value.length,

  pending: proposals.value.filter(
    (proposal) => proposal.status === 'pending',
  ).length,

  rejected: proposals.value.filter(
    (proposal) => proposal.status === 'rejected',
  ).length,

  accepted: proposals.value.filter(
    (proposal) =>
      proposal.status === 'approved' ||
      proposal.status === 'merged',
  ).length,
}))

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

function proposalTypeLabel(
  proposal: CurriculumProposal,
): string {
  const labels = {
    create: 'Materie nouă',
    update: 'Actualizare',
    alias: 'Alias',
    merge_candidate: 'Posibil duplicat',
  }

  return labels[proposal.proposal_type]
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
    },
  ).format(date)
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
      return 'Propunerea nu mai poate fi procesată în starea curentă.'
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
        redirect: '/curriculum',
      },
    })

    return true
  }

  return false
}

async function loadProposals():
Promise<void> {
  loading.value = true
  loadError.value = null

  try {
    proposals.value =
      await getCurriculumProposals()
  } catch (error) {
    if (await handleExpiredSession(error)) {
      return
    }

    proposals.value = []

    loadError.value = requestErrorMessage(
      error,
      'Nu am putut încărca propunerile curriculare.',
    )
  } finally {
    loading.value = false
  }
}

function resetForm(): void {
  form.name = ''
  form.code = ''
  form.description = ''
  form.reason = ''
  formError.value = null
}

async function saveProposal():
Promise<void> {
  formError.value = null
  notice.value = null

  const name = form.name.trim()
  const code = form.code.trim()
  const description =
    form.description.trim()
  const reason = form.reason.trim()

  if (!name) {
    formError.value =
      'Numele materiei este obligatoriu.'

    return
  }

  saving.value = true

  try {
    await createCurriculumProposal({
      entity_type: 'subject',
      proposal_type: 'create',
      payload: {
        name,
        ...(code
          ? {
              code,
            }
          : {}),
        ...(description
          ? {
              description,
            }
          : {}),
      },
      reason: reason || null,
    })

    notice.value =
      'Propunerea a fost trimisă pentru moderare.'

    resetForm()

    await loadProposals()
  } catch (error) {
    if (await handleExpiredSession(error)) {
      return
    }

    formError.value = requestErrorMessage(
      error,
      'Propunerea nu a putut fi trimisă.',
    )
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  void loadProposals()
})
</script>

<template>
  <section class="page curriculum-page">
    <header class="curriculum-heading">
      <div>
        <span class="eyebrow">
          CURRICULUM COLABORATIV
        </span>

        <h1>Curriculum</h1>

        <p>
          Propune îmbunătățiri ale structurii
          curriculare și urmărește deciziile
          moderatorilor.
        </p>
      </div>
    </header>

    <div class="curriculum-stats">
      <div>
        <span>Total</span>
        <strong>{{ stats.total }}</strong>
      </div>

      <div>
        <span>În așteptare</span>
        <strong>{{ stats.pending }}</strong>
      </div>

      <div>
        <span>Respinse</span>
        <strong>{{ stats.rejected }}</strong>
      </div>

      <div>
        <span>Acceptate</span>
        <strong>{{ stats.accepted }}</strong>
      </div>
    </div>

    <div
      v-if="notice"
      class="curriculum-notice"
      role="status"
    >
      {{ notice }}
    </div>

    <div
      v-if="loadError"
      class="curriculum-error"
      role="alert"
    >
      <span>{{ loadError }}</span>

      <button
        type="button"
        :disabled="loading"
        @click="loadProposals"
      >
        Reîncearcă
      </button>
    </div>

    <div class="curriculum-layout">
      <section class="curriculum-proposals">
        <div class="curriculum-section-heading">
          <div>
            <span>PROPUNERILE MELE</span>
            <h2>Activitate curriculară</h2>
          </div>

          <span>{{ proposals.length }}</span>
        </div>

        <div
          v-if="
            loading &&
            proposals.length === 0
          "
          class="curriculum-empty"
        >
          Se încarcă propunerile...
        </div>

        <div
          v-else-if="
            proposals.length === 0
          "
          class="curriculum-empty"
        >
          <strong>
            Nu ai încă propuneri.
          </strong>

          <span>
            Poți propune prima materie
            folosind formularul alăturat.
          </span>
        </div>

        <div
          v-else
          class="curriculum-proposal-list"
        >
          <article
            v-for="proposal in proposals"
            :key="proposal.id"
            class="curriculum-proposal-card"
          >
            <div class="proposal-top">
              <span class="proposal-type">
                {{
                  proposalTypeLabel(
                    proposal,
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

            <h3>
              {{
                proposalTitle(
                  proposal,
                )
              }}
            </h3>

            <p
              v-if="proposal.reason"
              class="proposal-reason"
            >
              {{ proposal.reason }}
            </p>

            <div class="proposal-meta">
              <span>
                #{{ proposal.id }}
              </span>

              <span>
                {{
                  formatDate(
                    proposal.created_at,
                  )
                }}
              </span>
            </div>

            <div
              v-if="proposal.review_note"
              class="proposal-review-note"
            >
              <strong>
                Feedback moderator
              </strong>

              <span>
                {{ proposal.review_note }}
              </span>
            </div>
          </article>
        </div>
      </section>

      <section
        id="curriculum-editor"
        class="curriculum-editor"
      >
        <div class="curriculum-section-heading">
          <div>
            <span>PROPUNERE NOUĂ</span>

            <h2>
              Propune o materie
            </h2>
          </div>
        </div>

        <p class="curriculum-help">
          În această etapă poți propune
          introducerea unei materii noi.
          Propunerea va fi verificată înainte
          de a deveni parte din curriculum.
        </p>

        <form
          class="curriculum-form"
          @submit.prevent="saveProposal"
        >
          <label>
            <span>Nume materie *</span>

            <input
              v-model="form.name"
              type="text"
              maxlength="255"
              required
              placeholder="Ex. Educație media"
            >
          </label>

          <label>
            <span>Cod propus</span>

            <input
              v-model="form.code"
              type="text"
              maxlength="64"
              placeholder="Ex. EDU-MEDIA"
            >
          </label>

          <label>
            <span>Descriere</span>

            <textarea
              v-model="form.description"
              rows="5"
              placeholder="Descrie pe scurt materia propusă..."
            />
          </label>

          <label>
            <span>
              Motivul propunerii
            </span>

            <textarea
              v-model="form.reason"
              rows="4"
              maxlength="2000"
              placeholder="De ce ar trebui introdusă această materie?"
            />
          </label>

          <div
            v-if="formError"
            class="curriculum-form-error"
            role="alert"
          >
            {{ formError }}
          </div>

          <button
            type="submit"
            class="curriculum-submit"
            :disabled="saving"
          >
            {{
              saving
                ? 'Se trimite...'
                : 'Trimite propunerea'
            }}
          </button>
        </form>
      </section>
    </div>
  </section>
</template>

<style scoped>
.curriculum-page {
  padding-top: 54px;
}

.curriculum-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 30px;
}

.curriculum-heading > div {
  max-width: 720px;
}

.curriculum-heading h1 {
  margin: 0;
  color: var(--text);
  font-size: 48px;
  letter-spacing: -0.04em;
}

.curriculum-heading p {
  margin: 18px 0 0;
  color: var(--muted);
  font-size: 16px;
  line-height: 1.7;
}

.curriculum-stats {
  display: grid;
  grid-template-columns:
    repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 32px;
}

.curriculum-stats > div {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
}

.curriculum-stats span,
.curriculum-stats strong {
  display: block;
}

.curriculum-stats span {
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.curriculum-stats strong {
  margin-top: 6px;
  color: var(--text);
  font-size: 24px;
}

.curriculum-notice,
.curriculum-error {
  margin-top: 18px;
  padding: 13px 15px;
  border-radius: 10px;
  font-size: 12px;
  line-height: 1.5;
}

.curriculum-notice {
  background: #ecfdf5;
  color: #047857;
}

.curriculum-error {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  background: #fff1f2;
  color: #be123c;
}

.curriculum-error button {
  border: 0;
  background: transparent;
  color: inherit;
  font-weight: 800;
  cursor: pointer;
}

.curriculum-layout {
  display: grid;
  grid-template-columns:
    minmax(0, 0.9fr)
    minmax(380px, 1.1fr);
  gap: 24px;
  align-items: start;
  margin-top: 28px;
}

.curriculum-proposals,
.curriculum-editor {
  padding: 22px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.curriculum-editor {
  position: sticky;
  top: 96px;
}

.curriculum-section-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 18px;
}

.curriculum-section-heading
  > div
  > span {
  display: block;
  margin-bottom: 5px;
  color: var(--primary);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.curriculum-section-heading h2 {
  margin: 0;
  color: var(--text);
  font-size: 18px;
}

.curriculum-section-heading
  > span {
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

.curriculum-proposal-list {
  display: grid;
  gap: 12px;
}

.curriculum-proposal-card {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 11px;
}

.proposal-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
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

.proposal-status {
  background: var(--background);
  color: var(--muted);
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

.curriculum-proposal-card h3 {
  margin: 12px 0 0;
  color: var(--text);
  font-size: 15px;
  line-height: 1.4;
}

.proposal-reason {
  margin: 7px 0 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.6;
}

.proposal-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
  margin-top: 11px;
  color: var(--muted);
  font-size: 9px;
}

.proposal-review-note {
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

.curriculum-empty {
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

.curriculum-empty strong {
  color: var(--text);
}

.curriculum-help {
  margin: -5px 0 18px;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.6;
}

.curriculum-form {
  display: grid;
  gap: 15px;
}

.curriculum-form label {
  display: grid;
  gap: 6px;
}

.curriculum-form label > span {
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.curriculum-form input,
.curriculum-form textarea {
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

.curriculum-form input {
  min-height: 40px;
}

.curriculum-form textarea {
  resize: vertical;
  line-height: 1.6;
}

.curriculum-form input:focus,
.curriculum-form textarea:focus {
  border-color: var(--primary);
  box-shadow:
    0 0 0 3px
    rgba(49, 86, 211, 0.1);
}

.curriculum-form-error {
  padding: 10px 12px;
  border-radius: 8px;
  background: #fff1f2;
  color: #be123c;
  font-size: 11px;
}

.curriculum-submit {
  min-height: 42px;
  border: 0;
  border-radius: 8px;
  background: var(--primary);
  color: #fff;
  font-weight: 800;
  cursor: pointer;
}

.curriculum-submit:hover:not(
  :disabled
) {
  background: var(--primary-dark);
}

.curriculum-submit:disabled {
  opacity: 0.6;
  cursor: wait;
}

@media (max-width: 1050px) {
  .curriculum-layout {
    grid-template-columns: 1fr;
  }

  .curriculum-editor {
    position: static;
  }
}

@media (max-width: 700px) {
  .curriculum-stats {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .curriculum-heading {
    display: block;
  }
}

@media (max-width: 480px) {
  .curriculum-heading h1 {
    font-size: 38px;
  }

  .curriculum-proposals,
  .curriculum-editor {
    padding: 17px;
  }
}
</style>
