<script setup lang="ts">
import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'

import {
  createAdminUser,
  getAdminUsers,
  resetAdminUserPassword,
  updateAdminUser,
} from '../api/admin'
import { ApiError } from '../api/client'
import { useAuthStore } from '../stores/auth'

import type {
  AdminUser,
  AdminUserRole,
} from '../types/admin'

const router = useRouter()
const authStore = useAuthStore()

const users = ref<AdminUser[]>([])
const selectedUserId = ref<number | null>(null)

const loading = ref(false)
const creating = ref(false)
const saving = ref(false)
const resettingPassword = ref(false)

const error = ref<string | null>(null)
const notice = ref<string | null>(null)
const createError = ref<string | null>(null)
const editError = ref<string | null>(null)
const passwordError = ref<string | null>(null)

const createForm = reactive({
  name: '',
  email: '',
  role: 'teacher' as AdminUserRole,
  password: '',
  passwordConfirmation: '',
})

const editForm = reactive({
  name: '',
  email: '',
  role: 'teacher' as AdminUserRole,
  isActive: true,
})

const passwordForm = reactive({
  password: '',
  passwordConfirmation: '',
})

const roles: Array<{
  value: AdminUserRole
  label: string
}> = [
  {
    value: 'teacher',
    label: 'Profesor',
  },
  {
    value: 'moderator',
    label: 'Moderator',
  },
  {
    value: 'admin',
    label: 'Administrator',
  },
]

const selectedUser = computed(
  () =>
    users.value.find(
      (user) => user.id === selectedUserId.value,
    ) ?? null,
)

const selectedUserIsCurrent = computed(
  () =>
    selectedUser.value?.id === authStore.user?.id,
)

const activeCount = computed(
  () =>
    users.value.filter(
      (user) => user.is_active,
    ).length,
)

const teacherCount = computed(
  () =>
    users.value.filter(
      (user) => user.role === 'teacher',
    ).length,
)

const moderatorCount = computed(
  () =>
    users.value.filter(
      (user) => user.role === 'moderator',
    ).length,
)

const adminCount = computed(
  () =>
    users.value.filter(
      (user) => user.role === 'admin',
    ).length,
)

function roleLabel(role: AdminUserRole): string {
  return roles.find(
    (item) => item.value === role,
  )?.label ?? role
}

function firstValidationMessage(
  payload: unknown,
): string | null {
  if (
    !payload ||
    typeof payload !== 'object' ||
    !('errors' in payload)
  ) {
    return null
  }

  const errors = (
    payload as {
      errors?: Record<string, unknown>
    }
  ).errors

  if (!errors || typeof errors !== 'object') {
    return null
  }

  for (const value of Object.values(errors)) {
    if (
      Array.isArray(value) &&
      typeof value[0] === 'string'
    ) {
      return value[0]
    }

    if (typeof value === 'string') {
      return value
    }
  }

  return null
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
      return 'Operația nu poate fi efectuată în starea curentă a contului.'
    }

    if (caught.status === 422) {
      return (
        firstValidationMessage(caught.payload) ??
        'Verifică datele completate.'
      )
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
        redirect: '/admin/users',
      },
    })

    return true
  }

  return false
}

function populateEditForm(user: AdminUser): void {
  editForm.name = user.name
  editForm.email = user.email
  editForm.role = user.role
  editForm.isActive = user.is_active
}

function clearPasswordForm(): void {
  passwordForm.password = ''
  passwordForm.passwordConfirmation = ''
  passwordError.value = null
}

function selectUser(user: AdminUser): void {
  selectedUserId.value = user.id

  populateEditForm(user)
  clearPasswordForm()

  editError.value = null
  notice.value = null
}

async function loadUsers(): Promise<void> {
  const previousSelection = selectedUserId.value

  loading.value = true
  error.value = null

  try {
    users.value = await getAdminUsers()

    if (
      previousSelection &&
      users.value.some(
        (user) => user.id === previousSelection,
      )
    ) {
      selectedUserId.value = previousSelection
    } else {
      selectedUserId.value =
        users.value[0]?.id ?? null
    }

    if (selectedUser.value) {
      populateEditForm(selectedUser.value)
    }
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    users.value = []
    selectedUserId.value = null

    error.value = requestErrorMessage(
      caught,
      'Lista utilizatorilor nu a putut fi încărcată.',
    )
  } finally {
    loading.value = false
  }
}

function resetCreateForm(): void {
  createForm.name = ''
  createForm.email = ''
  createForm.role = 'teacher'
  createForm.password = ''
  createForm.passwordConfirmation = ''
  createError.value = null
}

async function createUser(): Promise<void> {
  createError.value = null
  notice.value = null

  const name = createForm.name.trim()
  const email = createForm.email.trim().toLowerCase()

  if (!name || !email) {
    createError.value =
      'Numele și adresa de e-mail sunt obligatorii.'
    return
  }

  if (createForm.password.length < 8) {
    createError.value =
      'Parola trebuie să conțină cel puțin 8 caractere.'
    return
  }

  if (
    createForm.password !==
    createForm.passwordConfirmation
  ) {
    createError.value =
      'Confirmarea parolei nu corespunde.'
    return
  }

  creating.value = true

  try {
    const created = await createAdminUser({
      name,
      email,
      role: createForm.role,
      password: createForm.password,
      password_confirmation:
        createForm.passwordConfirmation,
    })

    resetCreateForm()

    notice.value =
      `Utilizatorul ${created.name} a fost creat.`

    selectedUserId.value = created.id

    await loadUsers()
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    createError.value = requestErrorMessage(
      caught,
      'Utilizatorul nu a putut fi creat.',
    )
  } finally {
    creating.value = false
  }
}

async function saveUser(): Promise<void> {
  if (!selectedUser.value) {
    return
  }

  editError.value = null
  notice.value = null

  const name = editForm.name.trim()
  const email = editForm.email.trim().toLowerCase()

  if (!name || !email) {
    editError.value =
      'Numele și adresa de e-mail sunt obligatorii.'
    return
  }

  saving.value = true

  try {
    const payload = {
      name,
      email,
      ...(
        selectedUserIsCurrent.value
          ? {}
          : {
              role: editForm.role,
              is_active: editForm.isActive,
            }
      ),
    }

    const updated = await updateAdminUser(
      selectedUser.value.id,
      payload,
    )

    notice.value =
      `Contul ${updated.name} a fost actualizat.`

    await loadUsers()
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    editError.value = requestErrorMessage(
      caught,
      'Contul nu a putut fi actualizat.',
    )
  } finally {
    saving.value = false
  }
}

async function resetPassword(): Promise<void> {
  if (!selectedUser.value) {
    return
  }

  passwordError.value = null
  notice.value = null

  if (passwordForm.password.length < 8) {
    passwordError.value =
      'Parola trebuie să conțină cel puțin 8 caractere.'
    return
  }

  if (
    passwordForm.password !==
    passwordForm.passwordConfirmation
  ) {
    passwordError.value =
      'Confirmarea parolei nu corespunde.'
    return
  }

  resettingPassword.value = true

  try {
    await resetAdminUserPassword(
      selectedUser.value.id,
      {
        password: passwordForm.password,
        password_confirmation:
          passwordForm.passwordConfirmation,
      },
    )

    notice.value =
      `Parola pentru ${selectedUser.value.name} a fost resetată.`

    clearPasswordForm()
  } catch (caught) {
    if (await handleExpiredSession(caught)) {
      return
    }

    passwordError.value = requestErrorMessage(
      caught,
      'Parola nu a putut fi resetată.',
    )
  } finally {
    resettingPassword.value = false
  }
}

onMounted(() => {
  void loadUsers()
})
</script>

<template>
  <section class="page admin-users-page">
    <header class="admin-heading">
      <div>
        <span class="eyebrow">ADMINISTRARE</span>

        <h1>Utilizatori</h1>

        <p>
          Gestionează conturile profesorilor,
          moderatorilor și administratorilor OpenEdu.
        </p>
      </div>

      <button
        type="button"
        class="secondary-button"
        :disabled="loading"
        @click="loadUsers"
      >
        {{
          loading
            ? 'Se actualizează...'
            : 'Actualizează'
        }}
      </button>
    </header>

    <div class="admin-stats">
      <div class="stat-card">
        <span>Total conturi</span>
        <strong>{{ users.length }}</strong>
      </div>

      <div class="stat-card">
        <span>Active</span>
        <strong>{{ activeCount }}</strong>
      </div>

      <div class="stat-card">
        <span>Profesori</span>
        <strong>{{ teacherCount }}</strong>
      </div>

      <div class="stat-card">
        <span>Moderatori</span>
        <strong>{{ moderatorCount }}</strong>
      </div>

      <div class="stat-card">
        <span>Administratori</span>
        <strong>{{ adminCount }}</strong>
      </div>
    </div>

    <div
      v-if="notice"
      class="notice"
      role="status"
    >
      {{ notice }}
    </div>

    <div
      v-if="error"
      class="error-message"
      role="alert"
    >
      {{ error }}
    </div>

    <div class="admin-grid">
      <section class="panel create-panel">
        <div class="panel-heading">
          <span>CONT NOU</span>
          <h2>Adaugă utilizator</h2>
        </div>

        <form
          class="admin-form"
          @submit.prevent="createUser"
        >
          <label>
            <span>Nume</span>
            <input
              v-model="createForm.name"
              type="text"
              autocomplete="off"
              required
            >
          </label>

          <label>
            <span>E-mail</span>
            <input
              v-model="createForm.email"
              type="email"
              autocomplete="off"
              required
            >
          </label>

          <label>
            <span>Rol</span>
            <select v-model="createForm.role">
              <option
                v-for="role in roles"
                :key="role.value"
                :value="role.value"
              >
                {{ role.label }}
              </option>
            </select>
          </label>

          <label>
            <span>Parolă inițială</span>
            <input
              v-model="createForm.password"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            >
          </label>

          <label>
            <span>Confirmă parola</span>
            <input
              v-model="createForm.passwordConfirmation"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            >
          </label>

          <div
            v-if="createError"
            class="form-error"
            role="alert"
          >
            {{ createError }}
          </div>

          <button
            type="submit"
            class="primary-button create-user-button"
            :disabled="creating"
          >
            {{
              creating
                ? 'Se creează...'
                : 'Creează utilizator'
            }}
          </button>
        </form>
      </section>

      <section class="panel users-panel">
        <div class="panel-heading users-heading">
          <div>
            <span>CONTURI CENTRALE</span>
            <h2>Utilizatori existenți</h2>
          </div>

          <span class="counter">
            {{ users.length }}
          </span>
        </div>

        <div
          v-if="loading && users.length === 0"
          class="empty-state"
        >
          Se încarcă utilizatorii...
        </div>

        <div
          v-else-if="users.length === 0"
          class="empty-state"
        >
          Nu există utilizatori de administrat.
        </div>

        <div
          v-else
          class="users-list"
        >
          <button
            v-for="user in users"
            :key="user.id"
            type="button"
            class="user-card"
            :class="{
              selected:
                user.id === selectedUserId,
            }"
            @click="selectUser(user)"
          >
            <span class="user-avatar">
              {{
                user.name
                  .trim()
                  .charAt(0)
                  .toUpperCase()
              }}
            </span>

            <span class="user-summary">
              <span class="user-name">
                {{ user.name }}

                <small
                  v-if="user.id === authStore.user?.id"
                >
                  Tu
                </small>
              </span>

              <span class="user-email">
                {{ user.email }}
              </span>
            </span>

            <span class="user-meta">
              <span class="role-badge">
                {{ roleLabel(user.role) }}
              </span>

              <span
                class="status-badge"
                :class="{
                  inactive: !user.is_active,
                }"
              >
                {{
                  user.is_active
                    ? 'Activ'
                    : 'Inactiv'
                }}
              </span>
            </span>
          </button>
        </div>
      </section>
    </div>

    <div
      v-if="selectedUser"
      class="details-grid"
    >
      <section class="panel">
        <div class="panel-heading">
          <span>EDITARE CONT</span>
          <h2>{{ selectedUser.name }}</h2>
        </div>

        <div
          v-if="selectedUserIsCurrent"
          class="self-account-note"
        >
          Acesta este contul tău. Rolul și starea
          contului nu pot fi modificate de aici.
        </div>

        <form
          class="admin-form"
          @submit.prevent="saveUser"
        >
          <label>
            <span>Nume</span>
            <input
              v-model="editForm.name"
              type="text"
              required
            >
          </label>

          <label>
            <span>E-mail</span>
            <input
              v-model="editForm.email"
              type="email"
              required
            >
          </label>

          <label>
            <span>Rol</span>
            <select
              v-model="editForm.role"
              :disabled="selectedUserIsCurrent"
            >
              <option
                v-for="role in roles"
                :key="role.value"
                :value="role.value"
              >
                {{ role.label }}
              </option>
            </select>
          </label>

          <label class="toggle-row">
            <input
              v-model="editForm.isActive"
              type="checkbox"
              :disabled="selectedUserIsCurrent"
            >

            <span>
              Cont activ
            </span>
          </label>

          <div
            v-if="editError"
            class="form-error"
            role="alert"
          >
            {{ editError }}
          </div>

          <button
            type="submit"
            class="primary-button save-user-button"
            :disabled="saving"
          >
            {{
              saving
                ? 'Se salvează...'
                : 'Salvează modificările'
            }}
          </button>
        </form>
      </section>

      <section class="panel">
        <div class="panel-heading">
          <span>SECURITATE</span>
          <h2>Resetare parolă</h2>
        </div>

        <p class="panel-description">
          Setează o parolă nouă pentru
          <strong>{{ selectedUser.name }}</strong>.
          Sesiunile active ale contului vor fi
          invalidate de server.
        </p>

        <form
          class="admin-form"
          @submit.prevent="resetPassword"
        >
          <label>
            <span>Parolă nouă</span>
            <input
              v-model="passwordForm.password"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            >
          </label>

          <label>
            <span>Confirmă parola nouă</span>
            <input
              v-model="passwordForm.passwordConfirmation"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            >
          </label>

          <div
            v-if="passwordError"
            class="form-error"
            role="alert"
          >
            {{ passwordError }}
          </div>

          <button
            type="submit"
            class="secondary-button reset-password-button"
            :disabled="resettingPassword"
          >
            {{
              resettingPassword
                ? 'Se resetează...'
                : 'Resetează parola'
            }}
          </button>
        </form>
      </section>
    </div>
  </section>
</template>

<style scoped>
.admin-users-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.admin-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 2rem;
}

.admin-heading h1,
.panel-heading h2 {
  margin: 0;
}

.admin-heading p {
  max-width: 48rem;
  margin-bottom: 0;
}

.admin-stats {
  display: grid;
  grid-template-columns:
    repeat(5, minmax(0, 1fr));
  gap: 0.75rem;
}

.stat-card,
.panel {
  border: 1px solid #dfe4ea;
  border-radius: 0.8rem;
  background: #fff;
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 1rem;
}

.stat-card span {
  color: #64748b;
  font-size: 0.8rem;
}

.stat-card strong {
  font-size: 1.55rem;
}

.admin-grid,
.details-grid {
  display: grid;
  grid-template-columns:
    minmax(17rem, 0.8fr)
    minmax(26rem, 1.5fr);
  gap: 1.25rem;
  align-items: start;
}

.details-grid {
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
}

.panel {
  padding: 1.25rem;
}

.panel-heading {
  margin-bottom: 1.2rem;
}

.panel-heading > span,
.panel-heading div > span {
  display: block;
  margin-bottom: 0.25rem;
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.users-heading {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.counter {
  min-width: 2rem;
  height: 2rem;
  padding: 0 0.5rem;
  border-radius: 999px;
  background: #eef2f7;
  color: #334155 !important;
  line-height: 2rem;
  text-align: center;
}

.admin-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.admin-form label:not(.toggle-row) {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.admin-form label > span {
  font-size: 0.85rem;
  font-weight: 600;
}

.admin-form input:not([type="checkbox"]),
.admin-form select {
  width: 100%;
  box-sizing: border-box;
  padding: 0.7rem 0.8rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.55rem;
  background: #fff;
  font: inherit;
}

.admin-form input:focus,
.admin-form select:focus {
  outline: 2px solid #b8d8f8;
  outline-offset: 1px;
  border-color: #5b9bd5;
}

.admin-form select:disabled,
.admin-form input:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.primary-button,
.secondary-button {
  min-height: 2.6rem;
  padding: 0.65rem 1rem;
  border-radius: 0.55rem;
  font: inherit;
  font-weight: 650;
  cursor: pointer;
}

.primary-button {
  border: 1px solid #1e5791;
  background: #1e5791;
  color: #fff;
}

.secondary-button {
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #1e293b;
}

.primary-button:disabled,
.secondary-button:disabled {
  cursor: wait;
  opacity: 0.6;
}

.users-list {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.user-card {
  display: grid;
  grid-template-columns:
    auto minmax(0, 1fr) auto;
  gap: 0.8rem;
  align-items: center;
  width: 100%;
  padding: 0.8rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.65rem;
  background: #fff;
  color: inherit;
  text-align: left;
  cursor: pointer;
}

.user-card:hover {
  border-color: #94a3b8;
}

.user-card.selected {
  border-color: #5b9bd5;
  background: #f5f9fd;
}

.user-avatar {
  display: grid;
  place-items: center;
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 50%;
  background: #e8eef5;
  font-weight: 700;
}

.user-summary,
.user-meta {
  display: flex;
  flex-direction: column;
}

.user-summary {
  min-width: 0;
  gap: 0.15rem;
}

.user-name {
  font-weight: 700;
}

.user-name small {
  margin-left: 0.35rem;
  padding: 0.12rem 0.35rem;
  border-radius: 999px;
  background: #e6f3ff;
  color: #245a88;
  font-size: 0.65rem;
}

.user-email {
  overflow: hidden;
  color: #64748b;
  font-size: 0.8rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-meta {
  align-items: flex-end;
  gap: 0.3rem;
}

.role-badge,
.status-badge {
  padding: 0.18rem 0.45rem;
  border-radius: 999px;
  font-size: 0.7rem;
  white-space: nowrap;
}

.role-badge {
  background: #eef2f7;
}

.status-badge {
  background: #e8f7ee;
  color: #247044;
}

.status-badge.inactive {
  background: #f2f2f2;
  color: #777;
}

.notice,
.error-message,
.form-error,
.self-account-note {
  padding: 0.8rem 1rem;
  border-radius: 0.55rem;
}

.notice {
  border: 1px solid #b8dfc6;
  background: #f0faf4;
  color: #245f38;
}

.error-message,
.form-error {
  border: 1px solid #efc1c1;
  background: #fff5f5;
  color: #9f2d2d;
}

.form-error {
  font-size: 0.85rem;
}

.self-account-note {
  margin-bottom: 1rem;
  border: 1px solid #cbddec;
  background: #f5f9fd;
  color: #35536d;
  font-size: 0.85rem;
}

.toggle-row {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.toggle-row input {
  width: 1rem;
  height: 1rem;
}

.panel-description {
  margin-top: 0;
  color: #64748b;
  line-height: 1.55;
}

.empty-state {
  padding: 2rem 1rem;
  color: #64748b;
  text-align: center;
}

@media (max-width: 1000px) {
  .admin-stats {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }

  .admin-grid,
  .details-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 650px) {
  .admin-heading {
    flex-direction: column;
  }

  .admin-stats {
    grid-template-columns: 1fr;
  }

  .user-card {
    grid-template-columns:
      auto minmax(0, 1fr);
  }

  .user-meta {
    grid-column: 2;
    align-items: flex-start;
    flex-direction: row;
  }
}
</style>
