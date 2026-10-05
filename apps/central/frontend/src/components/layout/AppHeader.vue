<script setup lang="ts">
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function logout(): Promise<void> {
  await authStore.logout()
  await router.push({ name: 'home' })
}
</script>

<template>
  <header class="app-header">
    <RouterLink to="/" class="brand">
      <span class="brand-mark">O</span>

      <span class="brand-text">
        <strong>OpenEdu</strong>
        <small>România</small>
      </span>
    </RouterLink>

    <div class="header-actions">
      <template v-if="authStore.isAuthenticated">
        <span class="visitor-label">
          {{ authStore.user?.name }}
        </span>

        <button
          type="button"
          class="login-link header-logout"
          :disabled="authStore.loading"
          @click="logout"
        >
          Deconectare
        </button>
      </template>

      <template v-else>
        <span class="visitor-label">Vizitator</span>

        <RouterLink
          to="/login"
          class="login-link"
        >
          Autentificare
        </RouterLink>
      </template>
    </div>
  </header>
</template>

<style scoped>
.header-logout {
  cursor: pointer;
}

.header-logout:disabled {
  opacity: 0.6;
  cursor: wait;
}
</style>
