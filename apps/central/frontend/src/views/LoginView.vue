<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { ApiError } from '../api/client'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref<string | null>(null)

function errorMessage(value: unknown): string {
  if (
    value instanceof ApiError &&
    value.status === 401
  ) {
    return 'Adresa de e-mail sau parola nu sunt corecte.'
  }

  if (
    value instanceof ApiError &&
    value.status === 422
  ) {
    return 'Verifică datele introduse și încearcă din nou.'
  }

  if (
    value instanceof ApiError &&
    value.status === 429
  ) {
    return 'Prea multe încercări. Încearcă din nou puțin mai târziu.'
  }

  return 'Autentificarea nu a putut fi efectuată.'
}

async function submit(): Promise<void> {
  error.value = null

  try {
    await authStore.login(
      email.value.trim(),
      password.value,
    )

    const requestedRedirect = route.query.redirect

    if (
      typeof requestedRedirect === 'string' &&
      requestedRedirect.startsWith('/')
    ) {
      await router.replace(requestedRedirect)
      return
    }

    if (authStore.canModerate) {
      await router.replace({
        name: 'moderation',
      })
      return
    }

    await router.replace(
      authStore.canContribute
        ? { name: 'teacher' }
        : { name: 'home' },
    )
  } catch (requestError) {
    error.value = errorMessage(requestError)
  }
}
</script>

<template>
  <main class="login-page">
    <div class="login-card">
      <RouterLink to="/" class="login-brand">
        OpenEdu
      </RouterLink>

      <span class="eyebrow">AUTENTIFICARE</span>
      <h1>Bine ai revenit.</h1>

      <p>
        Autentifică-te pentru a accesa spațiul de lucru
        și instrumentele editoriale OpenEdu.
      </p>

      <form
        class="login-form"
        @submit.prevent="submit"
      >
        <label>
          <span>Adresă de e-mail</span>

          <input
            v-model="email"
            type="email"
            autocomplete="email"
            required
            maxlength="255"
            placeholder="profesor@exemplu.ro"
          >
        </label>

        <label>
          <span>Parolă</span>

          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
            placeholder="Parola ta"
          >
        </label>

        <div
          v-if="error"
          class="login-error"
          role="alert"
        >
          {{ error }}
        </div>

        <button
          type="submit"
          class="login-submit"
          :disabled="authStore.loading"
        >
          {{
            authStore.loading
              ? 'Se autentifică...'
              : 'Autentificare'
          }}
        </button>
      </form>

      <RouterLink
        to="/"
        class="login-back"
      >
        ← Înapoi la OpenEdu
      </RouterLink>
    </div>
  </main>
</template>

<style scoped>
.login-form {
  display: grid;
  gap: 16px;
}

.login-form label {
  display: grid;
  gap: 7px;
}

.login-form label span {
  color: var(--muted);
  font-size: 12px;
  font-weight: 700;
}

.login-form input {
  width: 100%;
  height: 46px;
  padding: 0 13px;
  border: 1px solid var(--border);
  border-radius: 9px;
  outline: none;
  background: #fff;
  color: var(--text);
}

.login-form input:focus {
  border-color: var(--primary);
  box-shadow:
    0 0 0 3px rgba(49, 86, 211, 0.1);
}

.login-error {
  padding: 11px 13px;
  border-radius: 8px;
  background: #fff1f2;
  color: #be123c;
  font-size: 12px;
  line-height: 1.5;
}

.login-submit {
  min-height: 46px;
  border: 0;
  border-radius: 9px;
  background: var(--primary);
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

.login-submit:hover:not(:disabled) {
  background: var(--primary-dark);
}

.login-submit:disabled {
  opacity: 0.6;
  cursor: wait;
}

.login-back {
  display: inline-block;
  margin-top: 24px;
  color: var(--muted);
  font-size: 12px;
  font-weight: 700;
}

.login-back:hover {
  color: var(--primary);
}
</style>
