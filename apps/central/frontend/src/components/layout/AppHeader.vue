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
      <span class="visitor-label">Vizitator</span>

      <RouterLink to="/login" class="login-link">
        Autentificare
      </RouterLink>
    </div>
  </header>
</template>
