<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'

import { useCatalogStore } from '../stores/catalog'

import type { ConceptPlacement } from '../types/catalog'

const catalogStore = useCatalogStore()

const {
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
} = storeToRefs(catalogStore)

interface ConceptGroup {
  id: number
  title: string
  description: string | null
  placements: ConceptPlacement[]
}

const conceptGroups = computed<ConceptGroup[]>(() => {
  const groups = new Map<number, ConceptGroup>()

  for (const placement of conceptPlacements.value) {
    const domain = placement.domain

    if (!groups.has(domain.id)) {
      groups.set(domain.id, {
        id: domain.id,
        title: domain.title,
        description: domain.description,
        placements: [],
      })
    }

    groups.get(domain.id)?.placements.push(placement)
  }

  return [...groups.values()]
})

async function retryLastRequest(): Promise<void> {
  if (selectedCurriculumSubject.value) {
    await catalogStore.selectCurriculumSubject(
      selectedCurriculumSubject.value,
    )

    return
  }

  if (selectedEducationLevel.value) {
    await catalogStore.selectEducationLevel(
      selectedEducationLevel.value,
    )

    return
  }

  await catalogStore.loadEducationLevels()
}

async function retryConceptResources(): Promise<void> {
  if (!selectedConceptPlacement.value) {
    return
  }

  await catalogStore.selectConcept(
    selectedConceptPlacement.value,
  )
}

onMounted(() => {
  if (educationLevels.value.length === 0) {
    void catalogStore.loadEducationLevels()
  }
})
</script>

<template>
  <section class="page catalog-page">
    <div class="page-heading catalog-heading">
      <span class="eyebrow">CATALOG EDUCAȚIONAL</span>

      <h1>Explorează programa</h1>

      <p>
        Alege nivelul de studiu, materia și apoi conceptul pe care vrei
        să îl aprofundezi.
      </p>
    </div>

    <div class="catalog-progress" aria-label="Progres navigare catalog">
      <div
        class="catalog-progress-step"
        :class="{
          'is-active': !selectedEducationLevel,
          'is-complete': selectedEducationLevel,
        }"
      >
        <span>01</span>
        <strong>Nivel</strong>
      </div>

      <div class="catalog-progress-line" />

      <div
        class="catalog-progress-step"
        :class="{
          'is-active':
            selectedEducationLevel && !selectedCurriculumSubject,
          'is-complete': selectedCurriculumSubject,
        }"
      >
        <span>02</span>
        <strong>Materie</strong>
      </div>

      <div class="catalog-progress-line" />

      <div
        class="catalog-progress-step"
        :class="{
          'is-active':
            selectedCurriculumSubject && !selectedConceptPlacement,
          'is-complete': selectedConceptPlacement,
        }"
      >
        <span>03</span>
        <strong>Concept</strong>
      </div>

      <div class="catalog-progress-line" />

      <div
        class="catalog-progress-step"
        :class="{
          'is-active': selectedConceptPlacement,
        }"
      >
        <span>04</span>
        <strong>Resurse</strong>
      </div>
    </div>

    <div v-if="error" class="catalog-error" role="alert">
      <div>
        <strong>Nu am putut încărca această parte a catalogului.</strong>
        <span>{{ error }}</span>
      </div>

      <button
        type="button"
        class="catalog-retry"
        :disabled="loading"
        @click="retryLastRequest"
      >
        Încearcă din nou
      </button>
    </div>

    <div class="catalog-grid">
      <section class="catalog-column">
        <div class="catalog-column-heading">
          <div>
            <span class="catalog-step-number">01</span>
            <h2>Nivel de studiu</h2>
          </div>

          <span
            v-if="selectedEducationLevel"
            class="catalog-selection-state"
          >
            Selectat
          </span>
        </div>

        <p class="catalog-column-description">
          Selectează nivelul pentru care cauți resurse educaționale.
        </p>

        <div
          v-if="loading && educationLevels.length === 0"
          class="catalog-loading"
        >
          <span class="catalog-spinner" />
          <span>Se încarcă nivelurile...</span>
        </div>

        <div
          v-else-if="educationLevels.length === 0 && !error"
          class="catalog-empty"
        >
          Nu există niveluri educaționale disponibile momentan.
        </div>

        <div v-else class="catalog-options">
          <button
            v-for="educationLevel in educationLevels"
            :key="educationLevel.id"
            type="button"
            class="catalog-option"
            :class="{
              'is-selected':
                selectedEducationLevel?.id === educationLevel.id,
            }"
            :aria-pressed="
              selectedEducationLevel?.id === educationLevel.id
            "
            :disabled="loading"
            @click="
              catalogStore.selectEducationLevel(educationLevel)
            "
          >
            <span class="catalog-option-main">
              <strong>{{ educationLevel.name }}</strong>

              <small v-if="educationLevel.education_stage">
                {{ educationLevel.education_stage }}
              </small>
            </span>

            <span class="catalog-option-arrow">→</span>
          </button>
        </div>
      </section>

      <section
        class="catalog-column"
        :class="{ 'is-disabled': !selectedEducationLevel }"
      >
        <div class="catalog-column-heading">
          <div>
            <span class="catalog-step-number">02</span>
            <h2>Materie</h2>
          </div>

          <span
            v-if="selectedCurriculumSubject"
            class="catalog-selection-state"
          >
            Selectată
          </span>
        </div>

        <p class="catalog-column-description">
          {{
            selectedEducationLevel
              ? `Materiile disponibile pentru ${selectedEducationLevel.name}.`
              : 'Mai întâi selectează un nivel de studiu.'
          }}
        </p>

        <div
          v-if="
            selectedEducationLevel &&
            loading &&
            curriculumSubjects.length === 0
          "
          class="catalog-loading"
        >
          <span class="catalog-spinner" />
          <span>Se încarcă materiile...</span>
        </div>

        <div
          v-else-if="
            selectedEducationLevel &&
            curriculumSubjects.length === 0 &&
            !loading &&
            !error
          "
          class="catalog-empty"
        >
          Nu există materii configurate pentru acest nivel.
        </div>

        <div
          v-else-if="selectedEducationLevel"
          class="catalog-options"
        >
          <button
            v-for="curriculumSubject in curriculumSubjects"
            :key="curriculumSubject.id"
            type="button"
            class="catalog-option"
            :class="{
              'is-selected':
                selectedCurriculumSubject?.id ===
                curriculumSubject.id,
            }"
            :aria-pressed="
              selectedCurriculumSubject?.id === curriculumSubject.id
            "
            :disabled="loading"
            @click="
              catalogStore.selectCurriculumSubject(
                curriculumSubject,
              )
            "
          >
            <span class="catalog-option-main">
              <strong>{{ curriculumSubject.subject.name }}</strong>

              <small v-if="curriculumSubject.subject.description">
                {{ curriculumSubject.subject.description }}
              </small>
            </span>

            <span class="catalog-option-arrow">→</span>
          </button>
        </div>

        <div v-else class="catalog-waiting">
          <span>01</span>
          <p>Selectează un nivel pentru a continua.</p>
        </div>
      </section>

      <section
        class="catalog-column catalog-concepts-column"
        :class="{ 'is-disabled': !selectedCurriculumSubject }"
      >
        <div class="catalog-column-heading">
          <div>
            <span class="catalog-step-number">03</span>
            <h2>Concepte</h2>
          </div>
        </div>

        <p class="catalog-column-description">
          {{
            selectedCurriculumSubject
              ? `Conceptele din ${selectedCurriculumSubject.subject.name}.`
              : 'Selectează o materie pentru a vedea conceptele.'
          }}
        </p>

        <div
          v-if="
            selectedCurriculumSubject &&
            loading &&
            conceptPlacements.length === 0
          "
          class="catalog-loading"
        >
          <span class="catalog-spinner" />
          <span>Se încarcă conceptele...</span>
        </div>

        <div
          v-else-if="
            selectedCurriculumSubject &&
            conceptPlacements.length === 0 &&
            !loading &&
            !error
          "
          class="catalog-empty"
        >
          Nu există concepte configurate pentru această materie.
        </div>

        <div
          v-else-if="selectedCurriculumSubject"
          class="concept-groups"
        >
          <section
            v-for="group in conceptGroups"
            :key="group.id"
            class="concept-group"
          >
            <div class="concept-group-heading">
              <h3>{{ group.title }}</h3>

              <p v-if="group.description">
                {{ group.description }}
              </p>
            </div>

            <div class="concept-list">
              <button
                v-for="placement in group.placements"
                :key="placement.id"
                type="button"
                class="concept-card"
                :class="{
                  'is-selected':
                    selectedConceptPlacement?.id === placement.id,
                }"
                :aria-pressed="
                  selectedConceptPlacement?.id === placement.id
                "
                :disabled="resourcesLoading"
                @click="catalogStore.selectConcept(placement)"
              >
                <div class="concept-card-content">
                  <div class="concept-card-meta">
                    <span
                      v-if="placement.is_core"
                      class="concept-core-badge"
                    >
                      Concept de bază
                    </span>

                    <span class="concept-code">
                      {{ placement.concept.code }}
                    </span>
                  </div>

                  <h4>{{ placement.concept.title }}</h4>

                  <p v-if="placement.concept.description">
                    {{ placement.concept.description }}
                  </p>
                </div>

                <span class="concept-card-arrow">→</span>
              </button>
            </div>
          </section>
        </div>

        <div v-else class="catalog-waiting">
          <span>02</span>
          <p>Selectează o materie pentru a continua.</p>
        </div>
      </section>
    </div>

    <section
      v-if="selectedConceptPlacement"
      class="catalog-resources"
      aria-live="polite"
    >
      <div class="catalog-resources-heading">
        <div>
          <span class="catalog-step-number">04</span>
          <h2>Resurse asociate</h2>

          <p>
            Resursele disponibile pentru
            <strong>
              {{ selectedConceptPlacement.concept.title }}
            </strong>.
          </p>
        </div>

        <span
          v-if="!resourcesLoading && !resourcesError"
          class="catalog-resource-count"
        >
          {{ conceptResources.length }}
          {{ conceptResources.length === 1 ? 'resursă' : 'resurse' }}
        </span>
      </div>

      <div
        v-if="resourcesLoading"
        class="catalog-loading catalog-resource-state"
      >
        <span class="catalog-spinner" />
        <span>Se încarcă resursele asociate...</span>
      </div>

      <div
        v-else-if="resourcesError"
        class="catalog-resource-error"
        role="alert"
      >
        <div>
          <strong>Nu am putut încărca resursele conceptului.</strong>
          <span>{{ resourcesError }}</span>
        </div>

        <button
          type="button"
          class="catalog-retry"
          :disabled="resourcesLoading"
          @click="retryConceptResources"
        >
          Încearcă din nou
        </button>
      </div>

      <div
        v-else-if="conceptResources.length === 0"
        class="catalog-empty catalog-resource-state"
      >
        Nu există momentan resurse publicate asociate acestui concept.
      </div>

      <div v-else class="catalog-resource-grid">
        <RouterLink
          v-for="linkedResource in conceptResources"
          :key="linkedResource.id"
          :to="{
            name: 'resource-detail',
            params: {
              resourceId: linkedResource.resource.id,
            },
          }"
          class="catalog-resource-card"
        >
          <div class="catalog-resource-card-top">
            <span class="catalog-resource-type">
              {{ linkedResource.resource.type }}
            </span>

            <span
              v-if="linkedResource.is_primary"
              class="catalog-resource-primary"
            >
              Resursă principală
            </span>
          </div>

          <h3>
            {{
              linkedResource.resource.version?.title ??
              linkedResource.resource.code
            }}
          </h3>

          <p v-if="linkedResource.resource.version?.summary">
            {{ linkedResource.resource.version.summary }}
          </p>

          <div class="catalog-resource-meta">
            <span>{{ linkedResource.resource.code }}</span>

            <span v-if="linkedResource.resource.version">
              Dificultate
              {{ linkedResource.resource.version.difficulty_level }}
            </span>

            <span v-if="linkedResource.resource.version">
              Complexitate
              {{ linkedResource.resource.version.complexity_level }}
            </span>

            <span v-if="linkedResource.resource.version">
              {{ linkedResource.resource.version.language_code }}
            </span>
          </div>
        </RouterLink>
      </div>
    </section>
  </section>
</template>

<style scoped>
.catalog-page {
  padding-top: 54px;
}

.catalog-heading {
  max-width: 760px;
}

.catalog-progress {
  display: flex;
  align-items: center;
  max-width: 820px;
  margin-top: 42px;
}

.catalog-progress-step {
  display: flex;
  align-items: center;
  gap: 9px;
  color: var(--muted);
}

.catalog-progress-step span {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border);
  border-radius: 50%;
  background: var(--surface);
  font-size: 10px;
  font-weight: 800;
}

.catalog-progress-step strong {
  font-size: 13px;
}

.catalog-progress-step.is-active {
  color: var(--primary);
}

.catalog-progress-step.is-active span {
  border-color: var(--primary);
  background: var(--primary);
  color: white;
}

.catalog-progress-step.is-complete span {
  border-color: #aab9f4;
  background: #eef2ff;
  color: var(--primary);
}

.catalog-progress-line {
  width: 60px;
  height: 1px;
  margin: 0 14px;
  background: var(--border);
}

.catalog-error {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-top: 34px;
  padding: 18px 20px;
  border: 1px solid #f2caca;
  border-radius: 12px;
  background: #fff7f7;
}

.catalog-error > div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.catalog-error strong {
  color: #8a2828;
  font-size: 14px;
}

.catalog-error span {
  color: #a05050;
  font-size: 13px;
}

.catalog-retry {
  flex: 0 0 auto;
  padding: 8px 13px;
  border: 1px solid #e3b6b6;
  border-radius: 8px;
  background: white;
  color: #8a2828;
  cursor: pointer;
  font-size: 12px;
  font-weight: 700;
}

.catalog-retry:disabled {
  cursor: default;
  opacity: 0.55;
}

.catalog-grid {
  display: grid;
  grid-template-columns:
    minmax(220px, 0.85fr)
    minmax(240px, 0.95fr)
    minmax(320px, 1.35fr);
  gap: 18px;
  align-items: start;
  margin-top: 32px;
}

.catalog-column {
  min-height: 390px;
  padding: 22px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--surface);
  box-shadow: 0 12px 30px rgba(44, 55, 90, 0.035);
}

.catalog-column.is-disabled {
  background: #fafbfc;
}

.catalog-column-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.catalog-column-heading > div {
  min-width: 0;
}

.catalog-step-number {
  display: block;
  margin-bottom: 7px;
  color: var(--primary);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.catalog-column h2 {
  margin: 0;
  color: var(--text);
  font-size: 20px;
  letter-spacing: -0.02em;
}

.catalog-selection-state {
  padding: 5px 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: var(--primary);
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
}

.catalog-column-description {
  min-height: 42px;
  margin: 11px 0 21px;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.55;
}

.catalog-options {
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.catalog-option {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 13px 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: white;
  color: var(--text);
  cursor: pointer;
  text-align: left;
  transition:
    border-color 0.15s ease,
    background 0.15s ease,
    box-shadow 0.15s ease,
    transform 0.15s ease;
}

.catalog-option:hover:not(:disabled) {
  border-color: #c9d3f5;
  box-shadow: 0 5px 16px rgba(49, 86, 211, 0.07);
  transform: translateY(-1px);
}

.catalog-option.is-selected {
  border-color: #a9b9f2;
  background: #f4f6ff;
}

.catalog-option:disabled {
  cursor: default;
}

.catalog-option-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.catalog-option-main strong {
  font-size: 13px;
  line-height: 1.35;
}

.catalog-option-main small {
  overflow: hidden;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.45;
  text-overflow: ellipsis;
}

.catalog-option-arrow,
.concept-card-arrow {
  flex: 0 0 auto;
  color: #a0a9b8;
  font-size: 15px;
}

.catalog-option.is-selected .catalog-option-arrow {
  color: var(--primary);
}

.catalog-loading,
.catalog-empty,
.catalog-waiting {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  border: 1px dashed #d8deea;
  border-radius: 10px;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.55;
  text-align: center;
}

.catalog-waiting {
  flex-direction: column;
}

.catalog-waiting > span {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #eef1f6;
  color: #8791a2;
  font-size: 10px;
  font-weight: 800;
}

.catalog-waiting p {
  margin: 0;
}

.catalog-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #dce2f4;
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: catalog-spin 0.7s linear infinite;
}

@keyframes catalog-spin {
  to {
    transform: rotate(360deg);
  }
}

.concept-groups {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.concept-group + .concept-group {
  padding-top: 22px;
  border-top: 1px solid var(--border);
}

.concept-group-heading {
  margin-bottom: 11px;
}

.concept-group-heading h3 {
  margin: 0;
  color: var(--text);
  font-size: 14px;
}

.concept-group-heading p {
  margin: 5px 0 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.5;
}

.concept-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.concept-card {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 13px 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: #fff;
  color: inherit;
  cursor: pointer;
  font: inherit;
  text-align: left;
  transition:
    border-color 0.15s ease,
    background 0.15s ease,
    box-shadow 0.15s ease,
    transform 0.15s ease;
}

.concept-card:hover:not(:disabled) {
  border-color: #c9d3f5;
  box-shadow: 0 5px 16px rgba(49, 86, 211, 0.07);
  transform: translateY(-1px);
}

.concept-card.is-selected {
  border-color: #a9b9f2;
  background: #f4f6ff;
}

.concept-card.is-selected .concept-card-arrow {
  color: var(--primary);
}

.concept-card:disabled {
  cursor: default;
}

.concept-card-content {
  min-width: 0;
}

.concept-card-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 6px;
}

.concept-core-badge {
  padding: 3px 6px;
  border-radius: 999px;
  background: #eef2ff;
  color: var(--primary);
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.concept-code {
  color: #9099a8;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.concept-card h4 {
  margin: 0;
  color: var(--text);
  font-size: 13px;
  line-height: 1.4;
}

.concept-card p {
  margin: 5px 0 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.5;
}

.catalog-resources {
  margin-top: 22px;
  padding: 26px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--surface);
  box-shadow: 0 12px 30px rgba(44, 55, 90, 0.035);
}

.catalog-resources-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 22px;
}

.catalog-resources-heading h2 {
  margin: 0;
  color: var(--text);
  font-size: 22px;
  letter-spacing: -0.02em;
}

.catalog-resources-heading p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.55;
}

.catalog-resources-heading p strong {
  color: var(--text);
}

.catalog-resource-count {
  flex: 0 0 auto;
  padding: 6px 10px;
  border-radius: 999px;
  background: #eef2ff;
  color: var(--primary);
  font-size: 11px;
  font-weight: 800;
}

.catalog-resource-state {
  min-height: 130px;
}

.catalog-resource-error {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  min-height: 90px;
  padding: 18px 20px;
  border: 1px solid #f2caca;
  border-radius: 12px;
  background: #fff7f7;
}

.catalog-resource-error > div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.catalog-resource-error strong {
  color: #8a2828;
  font-size: 14px;
}

.catalog-resource-error span {
  color: #a05050;
  font-size: 13px;
}

.catalog-resource-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.catalog-resource-card {
  min-width: 0;
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

.catalog-resource-card:hover {
  border-color: #c7d2fe;
  box-shadow: 0 10px 28px rgba(49, 86, 211, 0.08);
  transform: translateY(-2px);
}

.catalog-resource-card:focus-visible {
  outline: 3px solid rgba(49, 86, 211, 0.22);
  outline-offset: 3px;
}

.catalog-resource-card-top {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 11px;
}

.catalog-resource-type,
.catalog-resource-primary {
  padding: 4px 7px;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.catalog-resource-type {
  background: #f1f3f7;
  color: #697386;
}

.catalog-resource-primary {
  background: #eef2ff;
  color: var(--primary);
}

.catalog-resource-card h3 {
  margin: 0;
  color: var(--text);
  font-size: 15px;
  line-height: 1.4;
}

.catalog-resource-card > p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.6;
}

.catalog-resource-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 7px 12px;
  margin-top: 15px;
  padding-top: 12px;
  border-top: 1px solid #edf0f4;
  color: #8992a1;
  font-size: 10px;
  font-weight: 700;
}

@media (max-width: 1100px) {
  .catalog-grid {
    grid-template-columns: 1fr 1fr;
  }

  .catalog-concepts-column {
    grid-column: 1 / -1;
  }
}

@media (max-width: 720px) {
  .catalog-progress {
    align-items: flex-start;
  }

  .catalog-progress-step {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .catalog-progress-line {
    flex: 1;
    margin-top: 15px;
  }

  .catalog-grid {
    grid-template-columns: 1fr;
  }

  .catalog-concepts-column {
    grid-column: auto;
  }

  .catalog-resource-grid {
    grid-template-columns: 1fr;
  }

  .catalog-resources-heading,
  .catalog-resource-error {
    align-items: flex-start;
    flex-direction: column;
  }

  .catalog-error {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
