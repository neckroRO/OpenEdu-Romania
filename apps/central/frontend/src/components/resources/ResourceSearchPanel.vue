<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { getResources } from '../../api/resources'

import type {
  EducationalResourceDetail,
  ResourcePagination,
  ResourceSearchParams,
  ResourceSortDirection,
  ResourceSortField,
} from '../../types/resource'

interface SearchForm {
  q: string
  type: string
  languageCode: string
  difficultyLevel: string
  complexityLevel: string
  sort: ResourceSortField
  direction: ResourceSortDirection
  perPage: string
}

const route = useRoute()
const router = useRouter()

const filters = reactive<SearchForm>({
  q: '',
  type: '',
  languageCode: '',
  difficultyLevel: '',
  complexityLevel: '',
  sort: 'published_at',
  direction: 'desc',
  perPage: '20',
})

const resources = ref<EducationalResourceDetail[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const requestSequence = ref(0)

const pagination = ref<ResourcePagination>({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
  from: null,
  to: null,
})

const hasActiveFilters = computed(() =>
  Boolean(
    filters.q.trim() ||
      filters.type.trim() ||
      filters.languageCode.trim() ||
      filters.difficultyLevel ||
      filters.complexityLevel,
  ),
)

const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page

  if (last <= 1) {
    return [1]
  }

  let start = Math.max(1, current - 2)
  let end = Math.min(last, start + 4)

  start = Math.max(1, end - 4)

  const pages: number[] = []

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  return pages
})

function queryValue(name: string): string {
  const value = route.query[name]

  if (Array.isArray(value)) {
    return value[0] ?? ''
  }

  return value ?? ''
}

function isSortField(
  value: string,
): value is ResourceSortField {
  return [
    'published_at',
    'title',
    'difficulty_level',
  ].includes(value)
}

function isDirection(
  value: string,
): value is ResourceSortDirection {
  return value === 'asc' || value === 'desc'
}

function validLevel(value: string): string {
  const level = Number(value)

  if (
    Number.isInteger(level) &&
    level >= 1 &&
    level <= 10
  ) {
    return String(level)
  }

  return ''
}

function validPage(value: string): number {
  const page = Number(value)

  if (Number.isInteger(page) && page > 0) {
    return page
  }

  return 1
}

function syncFiltersFromRoute(): void {
  filters.q = queryValue('q')
  filters.type = queryValue('type')
  filters.languageCode =
    queryValue('language_code').toLowerCase()
  filters.difficultyLevel = validLevel(
    queryValue('difficulty_level'),
  )
  filters.complexityLevel = validLevel(
    queryValue('complexity_level'),
  )

  const sort = queryValue('sort')
  const direction = queryValue('direction')
  const perPage = queryValue('per_page')

  filters.sort = isSortField(sort)
    ? sort
    : 'published_at'

  filters.direction = isDirection(direction)
    ? direction
    : 'desc'

  filters.perPage = ['10', '20', '50'].includes(
    perPage,
  )
    ? perPage
    : '20'
}

function currentRequest(): ResourceSearchParams {
  return {
    q: filters.q.trim() || undefined,
    type: filters.type.trim() || undefined,
    language_code:
      filters.languageCode.trim().toLowerCase() ||
      undefined,
    difficulty_level: filters.difficultyLevel
      ? Number(filters.difficultyLevel)
      : undefined,
    complexity_level: filters.complexityLevel
      ? Number(filters.complexityLevel)
      : undefined,
    page: validPage(queryValue('page')),
    per_page: Number(filters.perPage),
    sort: filters.sort,
    direction: filters.direction,
  }
}

function buildQuery(
  page = 1,
): Record<string, string> {
  const query: Record<string, string> = {}

  if (filters.q.trim()) {
    query.q = filters.q.trim()
  }

  if (filters.type.trim()) {
    query.type = filters.type.trim()
  }

  if (filters.languageCode.trim()) {
    query.language_code =
      filters.languageCode.trim().toLowerCase()
  }

  if (filters.difficultyLevel) {
    query.difficulty_level =
      filters.difficultyLevel
  }

  if (filters.complexityLevel) {
    query.complexity_level =
      filters.complexityLevel
  }

  if (filters.sort !== 'published_at') {
    query.sort = filters.sort
  }

  if (filters.direction !== 'desc') {
    query.direction = filters.direction
  }

  if (filters.perPage !== '20') {
    query.per_page = filters.perPage
  }

  if (page > 1) {
    query.page = String(page)
  }

  return query
}

async function loadResources(): Promise<void> {
  const sequence = ++requestSequence.value

  loading.value = true
  error.value = null

  try {
    const result = await getResources(currentRequest())

    if (sequence !== requestSequence.value) {
      return
    }

    resources.value = result.data
    pagination.value = result.pagination
  } catch {
    if (sequence !== requestSequence.value) {
      return
    }

    error.value =
      'Nu am putut încărca resursele. Încearcă din nou.'

    resources.value = []
  } finally {
    if (sequence === requestSequence.value) {
      loading.value = false
    }
  }
}

async function applyFilters(): Promise<void> {
  const previousPath = route.fullPath

  await router.push({
    name: 'resources',
    query: buildQuery(),
  })

  if (route.fullPath === previousPath) {
    await loadResources()
  }
}

async function resetFilters(): Promise<void> {
  filters.q = ''
  filters.type = ''
  filters.languageCode = ''
  filters.difficultyLevel = ''
  filters.complexityLevel = ''
  filters.sort = 'published_at'
  filters.direction = 'desc'
  filters.perPage = '20'

  await applyFilters()
}

async function goToPage(page: number): Promise<void> {
  if (
    page < 1 ||
    page > pagination.value.last_page ||
    page === pagination.value.current_page
  ) {
    return
  }

  const previousPath = route.fullPath

  await router.push({
    name: 'resources',
    query: buildQuery(page),
  })

  if (route.fullPath === previousPath) {
    await loadResources()
  }
}

function formatPublishedAt(value: string | null): string {
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

watch(
  () => route.fullPath,
  () => {
    syncFiltersFromRoute()
    void loadResources()
  },
  { immediate: true },
)
</script>

<template>
  <section
    class="resource-search"
    aria-labelledby="resource-search-title"
  >
    <div class="resource-search-heading">
      <div>
        <span class="resource-search-eyebrow">
          TOATE RESURSELE
        </span>

        <h2 id="resource-search-title">
          Caută în biblioteca OpenEdu
        </h2>

        <p>
          Găsește direct o resursă după titlu sau rezumat
          și restrânge rezultatele folosind filtrele.
        </p>
      </div>

      <span
        v-if="!loading && !error"
        class="resource-search-total"
      >
        {{ pagination.total }}
        {{ pagination.total === 1 ? 'resursă' : 'resurse' }}
      </span>
    </div>

    <form
      class="resource-search-form"
      @submit.prevent="applyFilters"
    >
      <label class="resource-search-field search-main">
        <span>Căutare</span>

        <input
          v-model="filters.q"
          type="search"
          maxlength="255"
          placeholder="Ex: fracții, energie, literatură..."
        >
      </label>

      <label class="resource-search-field">
        <span>Tip</span>

        <input
          v-model="filters.type"
          type="text"
          maxlength="64"
          placeholder="Ex: lecție"
        >
      </label>

      <label class="resource-search-field field-language">
        <span>Limbă</span>

        <input
          v-model="filters.languageCode"
          type="text"
          maxlength="2"
          placeholder="ro"
          autocapitalize="none"
        >
      </label>

      <label class="resource-search-field">
        <span>Dificultate</span>

        <select v-model="filters.difficultyLevel">
          <option value="">Toate</option>
          <option
            v-for="level in 10"
            :key="`difficulty-${level}`"
            :value="String(level)"
          >
            {{ level }}
          </option>
        </select>
      </label>

      <label class="resource-search-field">
        <span>Complexitate</span>

        <select v-model="filters.complexityLevel">
          <option value="">Toate</option>
          <option
            v-for="level in 10"
            :key="`complexity-${level}`"
            :value="String(level)"
          >
            {{ level }}
          </option>
        </select>
      </label>

      <label class="resource-search-field">
        <span>Sortare</span>

        <select v-model="filters.sort">
          <option value="published_at">
            Data publicării
          </option>
          <option value="title">
            Titlu
          </option>
          <option value="difficulty_level">
            Dificultate
          </option>
        </select>
      </label>

      <label class="resource-search-field">
        <span>Ordine</span>

        <select v-model="filters.direction">
          <option value="desc">Descrescător</option>
          <option value="asc">Crescător</option>
        </select>
      </label>

      <label class="resource-search-field">
        <span>Pe pagină</span>

        <select v-model="filters.perPage">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
        </select>
      </label>

      <div class="resource-search-actions">
        <button
          type="submit"
          class="resource-search-submit"
          :disabled="loading"
        >
          Caută
        </button>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="resource-search-reset"
          :disabled="loading"
          @click="resetFilters"
        >
          Resetează
        </button>
      </div>
    </form>

    <div class="resource-search-results">
      <div class="resource-results-toolbar">
        <span v-if="loading">
          Se actualizează rezultatele...
        </span>

        <span
          v-else-if="
            pagination.from !== null &&
            pagination.to !== null
          "
        >
          Rezultatele
          {{ pagination.from }}–{{ pagination.to }}
          din {{ pagination.total }}
        </span>
      </div>

      <div
        v-if="loading && resources.length === 0"
        class="resource-search-state"
        aria-live="polite"
      >
        <span class="resource-search-spinner" />
        <span>Se încarcă resursele...</span>
      </div>

      <div
        v-else-if="error"
        class="resource-search-state is-error"
        role="alert"
      >
        <div>
          <strong>Nu am putut încărca biblioteca.</strong>
          <p>{{ error }}</p>
        </div>

        <button
          type="button"
          class="resource-search-retry"
          @click="loadResources"
        >
          Încearcă din nou
        </button>
      </div>

      <div
        v-else-if="resources.length === 0"
        class="resource-search-state"
      >
        <div>
          <strong>Nu am găsit resurse.</strong>
          <p>
            Modifică termenul de căutare sau elimină
            unul dintre filtre.
          </p>
        </div>
      </div>

      <div
        v-else
        class="resource-search-grid"
        :class="{ 'is-loading': loading }"
      >
        <RouterLink
          v-for="resource in resources"
          :key="resource.id"
          :to="{
            name: 'resource-detail',
            params: {
              resourceId: resource.id,
            },
            query: route.query,
          }"
          class="resource-search-card"
        >
          <div class="resource-search-card-top">
            <span class="resource-search-type">
              {{ resource.type }}
            </span>

            <span class="resource-search-code">
              {{ resource.code }}
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

          <div
            v-if="resource.version"
            class="resource-search-card-meta"
          >
            <span>
              Dificultate
              {{ resource.version.difficulty_level }}/10
            </span>

            <span>
              Complexitate
              {{ resource.version.complexity_level }}/10
            </span>

            <span>
              {{
                resource.version.language_code.toUpperCase()
              }}
            </span>

            <span>
              {{
                formatPublishedAt(
                  resource.version.published_at,
                )
              }}
            </span>
          </div>

          <span class="resource-search-open">
            Deschide resursa
            <span aria-hidden="true">→</span>
          </span>
        </RouterLink>
      </div>

      <nav
        v-if="pagination.last_page > 1"
        class="resource-pagination"
        aria-label="Paginarea resurselor"
      >
        <button
          type="button"
          :disabled="
            pagination.current_page === 1 || loading
          "
          @click="
            goToPage(pagination.current_page - 1)
          "
        >
          ←
          <span>Anterior</span>
        </button>

        <div class="resource-pagination-pages">
          <button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            :class="{
              'is-active':
                page === pagination.current_page,
            }"
            :aria-current="
              page === pagination.current_page
                ? 'page'
                : undefined
            "
            :disabled="loading"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
        </div>

        <button
          type="button"
          :disabled="
            pagination.current_page ===
              pagination.last_page ||
            loading
          "
          @click="
            goToPage(pagination.current_page + 1)
          "
        >
          <span>Următor</span>
          →
        </button>
      </nav>
    </div>
  </section>
</template>

<style scoped>
.resource-search {
  margin-top: 40px;
  padding: 26px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--surface);
}

.resource-search-heading {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  align-items: flex-start;
}

.resource-search-heading > div {
  max-width: 680px;
}

.resource-search-eyebrow {
  display: block;
  margin-bottom: 8px;
  color: var(--primary);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.resource-search-heading h2 {
  margin: 0;
  color: var(--text);
  font-size: 24px;
  line-height: 1.25;
}

.resource-search-heading p {
  margin: 9px 0 0;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.65;
}

.resource-search-total {
  flex: 0 0 auto;
  padding: 7px 10px;
  border-radius: 999px;
  background: #eef2ff;
  color: var(--primary);
  font-size: 11px;
  font-weight: 800;
}

.resource-search-form {
  display: grid;
  grid-template-columns:
    minmax(220px, 2fr)
    minmax(130px, 1fr)
    80px
    repeat(2, minmax(115px, 0.7fr));
  gap: 12px;
  margin-top: 24px;
  padding: 18px;
  border-radius: 12px;
  background: var(--background);
}

.resource-search-field {
  display: grid;
  gap: 6px;
  min-width: 0;
}

.resource-search-field > span {
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.resource-search-field input,
.resource-search-field select {
  width: 100%;
  min-width: 0;
  height: 40px;
  padding: 0 11px;
  border: 1px solid var(--border);
  border-radius: 8px;
  outline: none;
  background: #fff;
  color: var(--text);
  font: inherit;
  font-size: 12px;
}

.resource-search-field input:focus,
.resource-search-field select:focus {
  border-color: var(--primary);
  box-shadow:
    0 0 0 3px rgba(49, 86, 211, 0.1);
}

.resource-search-actions {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}

.resource-search-submit,
.resource-search-reset,
.resource-search-retry {
  min-height: 40px;
  padding: 0 15px;
  border-radius: 8px;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.resource-search-submit,
.resource-search-retry {
  border: 0;
  background: var(--primary);
  color: #fff;
}

.resource-search-submit:hover,
.resource-search-retry:hover {
  background: var(--primary-dark);
}

.resource-search-reset {
  border: 1px solid var(--border);
  background: #fff;
  color: var(--muted);
}

.resource-search-submit:disabled,
.resource-search-reset:disabled {
  opacity: 0.6;
  cursor: wait;
}

.resource-search-results {
  margin-top: 20px;
}

.resource-results-toolbar {
  min-height: 25px;
  color: var(--muted);
  font-size: 11px;
}

.resource-search-grid {
  display: grid;
  grid-template-columns:
    repeat(3, minmax(0, 1fr));
  gap: 14px;
  transition: opacity 0.18s ease;
}

.resource-search-grid.is-loading {
  opacity: 0.55;
}

.resource-search-card {
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: #fff;
  color: inherit;
  text-decoration: none;
  transition:
    border-color 0.18s ease,
    box-shadow 0.18s ease,
    transform 0.18s ease;
}

.resource-search-card:hover {
  border-color: #c7d2fe;
  box-shadow:
    0 10px 28px rgba(49, 86, 211, 0.08);
  transform: translateY(-2px);
}

.resource-search-card:focus-visible {
  outline: 3px solid rgba(49, 86, 211, 0.22);
  outline-offset: 3px;
}

.resource-search-card-top {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 11px;
}

.resource-search-type,
.resource-search-code {
  padding: 4px 7px;
  border-radius: 6px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.resource-search-type {
  background: #eef2ff;
  color: var(--primary);
}

.resource-search-code {
  background: var(--background);
  color: var(--muted);
}

.resource-search-card h3 {
  margin: 0;
  color: var(--text);
  font-size: 15px;
  line-height: 1.45;
}

.resource-search-card > p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.6;
}

.resource-search-card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 7px 11px;
  margin-top: 15px;
  padding-top: 12px;
  border-top: 1px solid var(--border);
  color: var(--muted);
  font-size: 10px;
}

.resource-search-open {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-top: auto;
  padding-top: 17px;
  color: var(--primary);
  font-size: 11px;
  font-weight: 800;
}

.resource-search-state {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  min-height: 100px;
  padding: 22px;
  border: 1px dashed var(--border);
  border-radius: 12px;
  color: var(--muted);
  font-size: 12px;
}

.resource-search-state strong {
  display: block;
  margin-bottom: 5px;
  color: var(--text);
}

.resource-search-state p {
  margin: 0;
  line-height: 1.6;
}

.resource-search-spinner {
  width: 26px;
  height: 26px;
  border: 3px solid var(--border);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: resource-search-spin 0.8s linear infinite;
}

.resource-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-top: 22px;
}

.resource-pagination > button,
.resource-pagination-pages button {
  height: 36px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: #fff;
  color: var(--text);
  font: inherit;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

.resource-pagination > button {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 0 12px;
}

.resource-pagination-pages {
  display: flex;
  gap: 6px;
}

.resource-pagination-pages button {
  width: 36px;
  padding: 0;
}

.resource-pagination-pages button.is-active {
  border-color: var(--primary);
  background: var(--primary);
  color: #fff;
}

.resource-pagination button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

@keyframes resource-search-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1100px) {
  .resource-search-form {
    grid-template-columns:
      repeat(3, minmax(0, 1fr));
  }

  .search-main {
    grid-column: span 2;
  }

  .resource-search-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .resource-search {
    padding: 20px;
  }

  .resource-search-heading {
    display: block;
  }

  .resource-search-total {
    display: inline-flex;
    margin-top: 14px;
  }

  .resource-search-form {
    grid-template-columns: 1fr 1fr;
  }

  .search-main {
    grid-column: 1 / -1;
  }

  .resource-search-actions {
    grid-column: 1 / -1;
  }

  .resource-search-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .resource-search-form {
    grid-template-columns: 1fr;
  }

  .search-main,
  .resource-search-actions {
    grid-column: auto;
  }

  .resource-search-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .resource-pagination {
    justify-content: space-between;
  }

  .resource-pagination > button span {
    display: none;
  }
}
</style>
