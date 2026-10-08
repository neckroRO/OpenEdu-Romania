<script setup lang="ts">
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
</script>

<template>
  <aside class="app-sidebar">
    <nav
      class="main-navigation"
      aria-label="Navigație principală"
    >
      <RouterLink to="/" class="nav-item">
        <span class="nav-icon">⌂</span>
        <span>Acasă</span>
      </RouterLink>

      <RouterLink to="/resources" class="nav-item">
        <span class="nav-icon">▤</span>
        <span>Resurse</span>
      </RouterLink>

      <RouterLink
        v-if="authStore.canContribute"
        to="/teacher"
        class="nav-item"
      >
        <span class="nav-icon">✎</span>
        <span>Zona profesorului</span>
      </RouterLink>

<RouterLink
  v-if="authStore.canContribute"
  to="/curriculum"
  class="nav-item"
>
  <span class="nav-icon">◇</span>
  <span>Curriculum</span>
</RouterLink>

<RouterLink
  v-if="authStore.canModerate"
  to="/curriculum/moderation"
  class="nav-item"
>
  <span class="nav-icon">◈</span>
  <span>Moderare curriculum</span>
</RouterLink>

      <RouterLink
        v-if="authStore.canModerate"
        to="/moderation"
        class="nav-item"
      >
        <span class="nav-icon">✓</span>
        <span>Moderare</span>
      </RouterLink>

      <RouterLink
        v-if="authStore.canAdmin"
        to="/admin/users"
        class="nav-item"
      >
        <span class="nav-icon">⚙</span>
        <span>Utilizatori</span>
      </RouterLink>
    </nav>

    <div class="sidebar-footer">
      <strong>OpenEdu</strong>
      <span>Învățare deschisă pentru toți.</span>
    </div>
  </aside>
</template>
