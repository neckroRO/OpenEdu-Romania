<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import { getResource } from '../api/resources'
import { ApiError } from '../api/client'

import type { EducationalResourceDetail } from '../types/resource'

type ResourceError = 'not-found' | 'request' | null

const route = useRoute()

const resource = ref<EducationalResourceDetail | null>(null)
const loading = ref(false)
const error = ref<ResourceError>(null)

const version = computed(() => resource.value?.version ?? null)

const publishedAt = computed(() => {
  if (!version.value?.published_at) {
    return null
  }

  const date = new Date(version.value.published_at)

  if (Number.isNaN(date.getTime())) {
    return null
  }

  return new Intl.DateTimeFormat('ro-RO', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(date)
})

const sourceUrl = computed(() => {
  const value = version.value?.source_url

  if (!value) {
    return null
  }

  try {
    const url = new URL(value)

    if (!['http:', 'https:'].includes(url.protocol)) {
      return null
    }

    return url.toString()
  } catch {
    return null
  }
})

async function loadResource(): Promise<void> {
  const rawResourceId = route.params.resourceId
  const resourceId = Number(rawResourceId)

  resource.value = null
  error.value = null

  if (
    !Number.isInteger(resourceId) ||
    resourceId <= 0
  ) {
    error.value = 'not-found'
    return
  }

  loading.value = true

  try {
    resource.value = await getResource(resourceId)

    if (!resource.value.version) {
      error.value = 'not-found'
      resource.value = null
    }
  } catch (requestError) {
    if (
      requestError instanceof ApiError &&
      requestError.status === 404
    ) {
      error.value = 'not-found'
    } else {
      error.value = 'request'
    }
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.resourceId,
  () => {
    void loadResource()
  },
  { immediate: true },
)
</script>

<template>
  <section
    class="page resource-detail-page"
    :aria-busy="loading"
  >
    <RouterLink
      :to="{ name: 'resources' }"
      class="resource-back"
    >
      <span aria-hidden="true">←</span>
      Înapoi la catalog
    </RouterLink>

    <div
      v-if="loading"
      class="resource-state"
      aria-live="polite"
    >
      <span class="resource-spinner" />
      <div>
        <strong>Se încarcă resursa...</strong>
        <p>Pregătim conținutul educațional.</p>
      </div>
    </div>

    <div
      v-else-if="error === 'not-found'"
      class="resource-state resource-state-error"
    >
      <span class="resource-state-code">404</span>

      <div>
        <strong>Resursa nu este disponibilă.</strong>
        <p>
          Resursa nu există, este inactivă sau nu are încă
          o versiune publicată.
        </p>

        <RouterLink
          :to="{ name: 'resources' }"
          class="resource-state-action"
        >
          Revino la catalog
        </RouterLink>
      </div>
    </div>

    <div
      v-else-if="error === 'request'"
      class="resource-state resource-state-error"
      role="alert"
    >
      <span class="resource-state-code">!</span>

      <div>
        <strong>Nu am putut încărca resursa.</strong>
        <p>
          A apărut o problemă la comunicarea cu serverul.
        </p>

        <button
          type="button"
          class="resource-state-action"
          @click="loadResource"
        >
          Încearcă din nou
        </button>
      </div>
    </div>

    <template v-else-if="resource && version">
      <header class="resource-header">
        <div class="resource-header-main">
          <div class="resource-eyebrow">
            <span>{{ resource.type }}</span>
            <span>{{ resource.code }}</span>
          </div>

          <h1>{{ version.title }}</h1>

          <p
            v-if="version.summary"
            class="resource-summary"
          >
            {{ version.summary }}
          </p>
        </div>

        <div class="resource-meta-grid">
          <div class="resource-meta-item">
            <span>Limbă</span>
            <strong>
              {{ version.language_code.toUpperCase() }}
            </strong>
          </div>

          <div class="resource-meta-item">
            <span>Dificultate</span>
            <strong>
              {{ version.difficulty_level }}/10
            </strong>
          </div>

          <div class="resource-meta-item">
            <span>Complexitate</span>
            <strong>
              {{ version.complexity_level }}/10
            </strong>
          </div>

          <div class="resource-meta-item">
            <span>Versiune</span>
            <strong>
              {{ version.version_number }}
            </strong>
          </div>
        </div>
      </header>

      <div class="resource-layout">
        <article class="resource-content">
          <div class="resource-section-heading">
            <span>CONȚINUT EDUCAȚIONAL</span>
            <h2>{{ version.title }}</h2>
          </div>

          <div
            v-if="version.content"
            class="resource-content-text"
          >
            {{ version.content }}
          </div>

          <div
            v-else
            class="resource-content-empty"
          >
            Această resursă nu conține momentan material
            textual pentru afișare directă.
          </div>
        </article>

        <aside class="resource-sidebar">
          <section class="resource-info-card">
            <h2>Despre resursă</h2>

            <dl>
              <div>
                <dt>Cod</dt>
                <dd>{{ resource.code }}</dd>
              </div>

              <div>
                <dt>Tip</dt>
                <dd>{{ resource.type }}</dd>
              </div>

              <div>
                <dt>Publicată</dt>
                <dd>{{ publishedAt ?? '—' }}</dd>
              </div>

              <div>
                <dt>Versiune</dt>
                <dd>{{ version.version_number }}</dd>
              </div>
            </dl>
          </section>

          <section
            v-if="sourceUrl"
            class="resource-info-card"
          >
            <h2>Sursă</h2>

            <p>
              Resursa include și o sursă externă asociată.
            </p>

            <a
              :href="sourceUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="resource-source-link"
            >
              Deschide sursa
              <span aria-hidden="true">↗</span>
            </a>
          </section>
        </aside>
      </div>
    </template>
  </section>
</template>

<style scoped>
.resource-detail-page {
  padding-top: 42px;
}

.resource-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 34px;
  color: var(--muted);
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  transition:
    color 0.18s ease,
    transform 0.18s ease;
}

.resource-back:hover {
  color: var(--primary);
  transform: translateX(-2px);
}

.resource-header {
  padding-bottom: 34px;
  border-bottom: 1px solid var(--border);
}

.resource-header-main {
  max-width: 820px;
}

.resource-eyebrow {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 15px;
}

.resource-eyebrow span {
  padding: 5px 9px;
  border-radius: 999px;
  background: #eef2ff;
  color: var(--primary);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.resource-eyebrow span + span {
  background: var(--background);
  color: var(--muted);
}

.resource-header h1 {
  max-width: 900px;
  margin: 0;
  color: var(--text);
  font-size: clamp(30px, 5vw, 48px);
  line-height: 1.08;
  letter-spacing: -0.035em;
}

.resource-summary {
  max-width: 760px;
  margin: 18px 0 0;
  color: var(--muted);
  font-size: 16px;
  line-height: 1.7;
}

.resource-meta-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  max-width: 760px;
  margin-top: 28px;
}

.resource-meta-item {
  padding: 13px 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--surface);
}

.resource-meta-item span,
.resource-meta-item strong {
  display: block;
}

.resource-meta-item span {
  margin-bottom: 5px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.resource-meta-item strong {
  color: var(--text);
  font-size: 14px;
}

.resource-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 260px;
  gap: 36px;
  align-items: start;
  margin-top: 36px;
}

.resource-content {
  min-width: 0;
  padding: 30px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.resource-section-heading {
  margin-bottom: 25px;
}

.resource-section-heading span {
  display: block;
  margin-bottom: 7px;
  color: var(--primary);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.resource-section-heading h2 {
  margin: 0;
  color: var(--text);
  font-size: 22px;
  line-height: 1.3;
}

.resource-content-text {
  color: var(--text);
  font-size: 15px;
  line-height: 1.85;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.resource-content-empty {
  padding: 22px;
  border-radius: 10px;
  background: var(--background);
  color: var(--muted);
  font-size: 13px;
  line-height: 1.6;
}

.resource-sidebar {
  display: grid;
  gap: 14px;
}

.resource-info-card {
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
}

.resource-info-card h2 {
  margin: 0 0 15px;
  color: var(--text);
  font-size: 14px;
}

.resource-info-card p {
  margin: 0 0 15px;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.6;
}

.resource-info-card dl {
  margin: 0;
}

.resource-info-card dl div {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 9px 0;
  border-top: 1px solid var(--border);
}

.resource-info-card dt,
.resource-info-card dd {
  margin: 0;
  font-size: 11px;
}

.resource-info-card dt {
  color: var(--muted);
}

.resource-info-card dd {
  color: var(--text);
  font-weight: 700;
  text-align: right;
  overflow-wrap: anywhere;
}

.resource-source-link,
.resource-state-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 38px;
  padding: 0 14px;
  border: 0;
  border-radius: 8px;
  background: var(--primary);
  color: #fff;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
}

.resource-source-link:hover,
.resource-state-action:hover {
  background: var(--primary-dark);
}

.resource-state {
  display: flex;
  align-items: center;
  gap: 18px;
  max-width: 680px;
  padding: 28px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.resource-state strong {
  display: block;
  margin-bottom: 5px;
  color: var(--text);
}

.resource-state p {
  margin: 0 0 15px;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.6;
}

.resource-state-code {
  flex: 0 0 auto;
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #fff1f2;
  color: #be123c;
  font-size: 15px;
  font-weight: 800;
}

.resource-spinner {
  flex: 0 0 auto;
  width: 28px;
  height: 28px;
  border: 3px solid var(--border);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: resource-spin 0.8s linear infinite;
}

@keyframes resource-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .resource-layout {
    grid-template-columns: 1fr;
  }

  .resource-sidebar {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .resource-detail-page {
    padding-top: 28px;
  }

  .resource-meta-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .resource-content {
    padding: 20px;
  }

  .resource-sidebar {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .resource-meta-grid {
    grid-template-columns: 1fr;
  }

  .resource-state {
    align-items: flex-start;
    padding: 20px;
  }
}
</style>
