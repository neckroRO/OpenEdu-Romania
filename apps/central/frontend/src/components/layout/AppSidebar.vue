<template>
  <aside class="app-sidebar">
    <nav class="main-navigation" aria-label="Navigație principală">
      <RouterLink to="/" class="nav-item">
        <span class="nav-icon">⌂</span>
        <span>Acasă</span>
      </RouterLink>

      <RouterLink to="/resources" class="nav-item">
        <span class="nav-icon">▤</span>
        <span>Resurse</span>
      </RouterLink>

      <RouterLink to="/teacher" class="nav-item">
        <span class="nav-icon">✎</span>
        <span>Zona profesorului</span>
      </RouterLink>
    </nav>

    <div class="sidebar-footer">
      <strong>OpenEdu</strong>
      <span>Învățare deschisă pentru toți.</span>
    </div>
  </aside>
</template>
