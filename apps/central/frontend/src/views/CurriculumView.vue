<script setup lang="ts">
import {
  computed,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'
import { useRouter } from 'vue-router'

import {
  createCurriculumProposal,
  getCurriculumProposals,
} from '../api/curriculum'
import { ApiError } from '../api/client'
import { useAuthStore } from '../stores/auth'

import type {
  CreateCurriculumProposalPayload,
  CurriculumEntityType,
  CurriculumProposal,
  CurriculumProposalStatus,
  CurriculumProposalType,
} from '../types/curriculum'

type ContributorEntityType =
  Extract<
    CurriculumEntityType,
    'domain' | 'concept'
  >

const router = useRouter()
const authStore = useAuthStore()

const proposals = ref<CurriculumProposal[]>([])

const loading = ref(false)
const saving = ref(false)

const loadError = ref<string | null>(null)
const formError = ref<string | null>(null)
const notice = ref<string | null>(null)

const form = reactive({
  entityType: 'domain' as ContributorEntityType,
  proposalType: 'create' as CurriculumProposalType,

  entityId: '',
  duplicateEntityId: '',

  curriculumSubjectId: '',
  parentDomainId: '',

  title: '',
  code: '',
  description: '',
  displayOrder: '',
  alias: '',

  reason: '',
})

const proposalTypeOptions = computed(() => {
  if (form.entityType === 'domain') {
    return [
      {
        value: 'create' as const,
        label: 'Creare domeniu',
      },
      {
        value: 'update' as const,
        label: 'Actualizare domeniu',
      },
    ]
  }

  return [
    {
      value: 'create' as const,
      label: 'Creare concept',
    },
    {
      value: 'update' as const,
      label: 'Actualizare concept',
    },
    {
      value: 'alias' as const,
      label: 'Alias concept',
    },
    {
      value: 'merge_candidate' as const,
      label: 'Posibil duplicat',
    },
  ]
})

const requiresEntityId = computed(
  () => form.proposalType !== 'create',
)

const isDomain = computed(
  () => form.entityType === 'domain',
)

const isConcept = computed(
  () => form.entityType === 'concept',
)

const isCreateOrUpdate = computed(
  () =>
    form.proposalType === 'create' ||
    form.proposalType === 'update',
)

const isAlias = computed(
  () => form.proposalType === 'alias',
)

const isMergeCandidate = computed(
  () =>
    form.proposalType === 'merge_candidate',
)

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

function entityLabel(
  entityType: CurriculumEntityType,
): string {
  const labels: Record<
    CurriculumEntityType,
    string
  > = {
    subject: 'Materie',
    domain: 'Domeniu',
    concept: 'Concept',
  }

  return labels[entityType]
}

function proposalTypeLabel(
  proposal: CurriculumProposal,
): string {
  if (proposal.proposal_type === 'create') {
    return `${entityLabel(proposal.entity_type)} nou`
  }

  const labels: Record<
    Exclude<CurriculumProposalType, 'create'>,
    string
  > = {
    update: 'Actualizare',
    alias: 'Alias',
    merge_candidate: 'Posibil duplicat',
  }

  return labels[proposal.proposal_type]
}

function proposalTitle(
  proposal: CurriculumProposal,
): string {
  const title = proposal.payload.title

  if (
    typeof title === 'string' &&
    title.trim()
  ) {
    return title
  }

  const name = proposal.payload.name

  if (
    typeof name === 'string' &&
    name.trim()
  ) {
    return name
  }

  const alias = proposal.payload.alias

  if (
    typeof alias === 'string' &&
    alias.trim()
  ) {
    return alias
  }

  if (proposal.entity_id !== null) {
    return `${entityLabel(
      proposal.entity_type,
    )} #${proposal.entity_id}`
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

function clearProposalFields(): void {
  form.entityId = ''
  form.duplicateEntityId = ''
  form.curriculumSubjectId = ''
  form.parentDomainId = ''
  form.title = ''
  form.code = ''
  form.description = ''
  form.displayOrder = ''
  form.alias = ''
}

function resetForm(): void {
  form.entityType = 'domain'
  form.proposalType = 'create'

  clearProposalFields()

  form.reason = ''
  formError.value = null
}

function normalizedInput(
  value: string | number | null | undefined,
): string {
  if (
    value === null ||
    value === undefined
  ) {
    return ''
  }

  return String(value).trim()
}

function positiveInteger(
  value: string | number | null | undefined,
): number | null {
  const normalized =
    normalizedInput(value)

  if (!normalized) {
    return null
  }

  const number = Number(normalized)

  if (
    !Number.isInteger(number) ||
    number <= 0
  ) {
    return null
  }

  return number
}

function nonNegativeInteger(
  value: string | number | null | undefined,
): number | null {
  const normalized =
    normalizedInput(value)

  if (!normalized) {
    return null
  }

  const number = Number(normalized)

  if (
    !Number.isInteger(number) ||
    number < 0
  ) {
    return null
  }

  return number
}

function buildProposal():
CreateCurriculumProposalPayload | null {
  const reason = form.reason.trim()

  const entityId =
    positiveInteger(form.entityId)

  if (
    requiresEntityId.value &&
    entityId === null
  ) {
    formError.value =
      'ID-ul entității este obligatoriu și trebuie să fie un număr pozitiv.'

    return null
  }

  if (isDomain.value) {
    if (form.proposalType === 'create') {
      const curriculumSubjectId =
        positiveInteger(
          form.curriculumSubjectId,
        )

      const title = form.title.trim()

      if (curriculumSubjectId === null) {
        formError.value =
          'ID-ul disciplinei curriculare este obligatoriu.'

        return null
      }

      if (!title) {
        formError.value =
          'Titlul domeniului este obligatoriu.'

        return null
      }

      const parentDomainId =
        positiveInteger(form.parentDomainId)

      const displayOrder =
        nonNegativeInteger(form.displayOrder)

      return {
        entity_type: 'domain',
        proposal_type: 'create',
        payload: {
          curriculum_subject_id:
            curriculumSubjectId,
          title,
          ...(parentDomainId !== null
            ? {
                parent_domain_id:
                  parentDomainId,
              }
            : {}),
          ...(form.description.trim()
            ? {
                description:
                  form.description.trim(),
              }
            : {}),
          ...(displayOrder !== null
            ? {
                display_order:
                  displayOrder,
              }
            : {}),
        },
        reason: reason || null,
      }
    }

    const payload:
      CreateCurriculumProposalPayload['payload'] =
      {}

    const curriculumSubjectId =
      positiveInteger(
        form.curriculumSubjectId,
      )

    const parentDomainRaw =
      normalizedInput(
        form.parentDomainId,
      )

    const parentDomainId =
      positiveInteger(parentDomainRaw)

    const title = form.title.trim()
    const description =
      form.description.trim()

    const displayOrderRaw =
      normalizedInput(
        form.displayOrder,
      )

    const displayOrder =
      nonNegativeInteger(displayOrderRaw)

    if (curriculumSubjectId !== null) {
      payload.curriculum_subject_id =
        curriculumSubjectId
    }

    if (parentDomainRaw) {
      if (parentDomainId === null) {
        formError.value =
          'ID-ul domeniului părinte trebuie să fie un număr pozitiv.'

        return null
      }

      payload.parent_domain_id =
        parentDomainId
    }

    if (title) {
      payload.title = title
    }

    if (description) {
      payload.description = description
    }

    if (displayOrderRaw) {
      if (displayOrder === null) {
        formError.value =
          'Ordinea de afișare trebuie să fie un număr întreg pozitiv sau zero.'

        return null
      }

      payload.display_order =
        displayOrder
    }

    if (Object.keys(payload).length === 0) {
      formError.value =
        'Completează cel puțin un câmp care trebuie actualizat.'

      return null
    }

    return {
      entity_type: 'domain',
      entity_id: entityId!,
      proposal_type: 'update',
      payload,
      reason: reason || null,
    }
  }

  if (form.proposalType === 'alias') {
    const alias = form.alias.trim()

    if (!alias) {
      formError.value =
        'Aliasul conceptului este obligatoriu.'

      return null
    }

    return {
      entity_type: 'concept',
      entity_id: entityId!,
      proposal_type: 'alias',
      payload: {
        alias,
      },
      reason: reason || null,
    }
  }

  if (
    form.proposalType ===
    'merge_candidate'
  ) {
    const duplicateEntityId =
      positiveInteger(
        form.duplicateEntityId,
      )

    if (duplicateEntityId === null) {
      formError.value =
        'ID-ul conceptului canonic este obligatoriu.'

      return null
    }

    if (duplicateEntityId === entityId) {
      formError.value =
        'Conceptul sursă și conceptul țintă trebuie să fie diferite.'

      return null
    }

    return {
      entity_type: 'concept',
      entity_id: entityId!,
      proposal_type:
        'merge_candidate',
      payload: {
        duplicate_entity_id:
          duplicateEntityId,
      },
      reason: reason || null,
    }
  }

  const code = form.code.trim()
  const title = form.title.trim()
  const description =
    form.description.trim()

  if (form.proposalType === 'create') {
    if (!code) {
      formError.value =
        'Codul conceptului este obligatoriu.'

      return null
    }

    if (!title) {
      formError.value =
        'Titlul conceptului este obligatoriu.'

      return null
    }

    return {
      entity_type: 'concept',
      proposal_type: 'create',
      payload: {
        code,
        title,
        ...(description
          ? {
              description,
            }
          : {}),
      },
      reason: reason || null,
    }
  }

  const payload:
    CreateCurriculumProposalPayload['payload'] =
    {}

  if (code) {
    payload.code = code
  }

  if (title) {
    payload.title = title
  }

  if (description) {
    payload.description = description
  }

  if (Object.keys(payload).length === 0) {
    formError.value =
      'Completează cel puțin un câmp care trebuie actualizat.'

    return null
  }

  return {
    entity_type: 'concept',
    entity_id: entityId!,
    proposal_type: 'update',
    payload,
    reason: reason || null,
  }
}

async function saveProposal():
Promise<void> {
  formError.value = null
  notice.value = null

  const payload = buildProposal()

  if (!payload) {
    return
  }

  saving.value = true

  try {
    await createCurriculumProposal(
      payload,
    )

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

watch(
  () => form.entityType,
  (entityType) => {
    clearProposalFields()

    form.proposalType =
      entityType === 'domain'
        ? 'create'
        : 'create'

    formError.value = null
  },
)

watch(
  () => form.proposalType,
  () => {
    clearProposalFields()
    formError.value = null
  },
)

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
            Poți propune primul domeniu
            sau concept folosind formularul
            alăturat.
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
              Contribuie la curriculum
            </h2>
          </div>
        </div>

        <p class="curriculum-help">
          Propune domenii și concepte noi,
          corectează entități existente sau
          semnalează concepte duplicate.
          Nicio modificare nu devine canonică
          fără moderare.
        </p>

        <form
          class="curriculum-form"
          @submit.prevent="saveProposal"
        >
          <label>
            <span>Tip entitate *</span>

            <select
              v-model="form.entityType"
              data-testid="entity-type"
            >
              <option value="domain">
                Domeniu
              </option>

              <option value="concept">
                Concept
              </option>
            </select>
          </label>

          <label>
            <span>Tip propunere *</span>

            <select
              v-model="form.proposalType"
              data-testid="proposal-type"
            >
              <option
                v-for="option in proposalTypeOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </label>

          <label
            v-if="requiresEntityId"
          >
            <span>
              ID {{ entityLabel(form.entityType) }} *
            </span>

            <input
              v-model="form.entityId"
              data-testid="entity-id"
              type="number"
              min="1"
              step="1"
              placeholder="Ex. 12"
            >
          </label>

          <template
            v-if="
              isDomain &&
              isCreateOrUpdate
            "
          >
            <label>
              <span>
                ID disciplină curriculară
                <template
                  v-if="form.proposalType === 'create'"
                >
                  *
                </template>
              </span>

              <input
                v-model="form.curriculumSubjectId"
                data-testid="curriculum-subject-id"
                type="number"
                min="1"
                step="1"
                placeholder="Ex. 4"
              >
            </label>

            <label>
              <span>
                Domeniu părinte
              </span>

              <input
                v-model="form.parentDomainId"
                data-testid="parent-domain-id"
                type="number"
                min="1"
                step="1"
                placeholder="ID domeniu părinte"
              >
            </label>

            <label>
              <span>
                Titlu
                <template
                  v-if="form.proposalType === 'create'"
                >
                  *
                </template>
              </span>

              <input
                v-model="form.title"
                data-testid="title"
                type="text"
                maxlength="255"
                placeholder="Ex. Algebră"
              >
            </label>

            <label>
              <span>Descriere</span>

              <textarea
                v-model="form.description"
                data-testid="description"
                rows="4"
                placeholder="Descrie domeniul..."
              />
            </label>

            <label>
              <span>Ordine afișare</span>

              <input
                v-model="form.displayOrder"
                data-testid="display-order"
                type="number"
                min="0"
                max="65535"
                step="1"
                placeholder="Ex. 10"
              >
            </label>
          </template>

          <template
            v-if="
              isConcept &&
              isCreateOrUpdate
            "
          >
            <label>
              <span>
                Cod
                <template
                  v-if="form.proposalType === 'create'"
                >
                  *
                </template>
              </span>

              <input
                v-model="form.code"
                data-testid="code"
                type="text"
                maxlength="128"
                placeholder="Ex. ALG-ECUATII"
              >
            </label>

            <label>
              <span>
                Titlu
                <template
                  v-if="form.proposalType === 'create'"
                >
                  *
                </template>
              </span>

              <input
                v-model="form.title"
                data-testid="title"
                type="text"
                maxlength="255"
                placeholder="Ex. Ecuații de gradul I"
              >
            </label>

            <label>
              <span>Descriere</span>

              <textarea
                v-model="form.description"
                data-testid="description"
                rows="4"
                placeholder="Descrie conceptul..."
              />
            </label>
          </template>

          <label
            v-if="
              isConcept &&
              isAlias
            "
          >
            <span>Alias *</span>

            <input
              v-model="form.alias"
              data-testid="alias"
              type="text"
              maxlength="255"
              placeholder="Ex. Ecuații liniare"
            >
          </label>

          <label
            v-if="
              isConcept &&
              isMergeCandidate
            "
          >
            <span>
              ID concept canonic *
            </span>

            <input
              v-model="form.duplicateEntityId"
              data-testid="duplicate-entity-id"
              type="number"
              min="1"
              step="1"
              placeholder="Conceptul în care va fi fuzionat"
            >
          </label>

          <label>
            <span>
              Motivul propunerii
            </span>

            <textarea
              v-model="form.reason"
              data-testid="reason"
              rows="4"
              maxlength="2000"
              placeholder="Explică de ce propui această modificare..."
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
.curriculum-form select,
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

.curriculum-form input,
.curriculum-form select {
  min-height: 40px;
}

.curriculum-form textarea {
  resize: vertical;
  line-height: 1.6;
}

.curriculum-form input:focus,
.curriculum-form select:focus,
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
