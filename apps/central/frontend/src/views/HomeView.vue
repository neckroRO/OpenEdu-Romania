<template>
  <section class="page home-page">
    <div class="hero">
      <div class="hero-content">
        <span class="eyebrow">OPENEDU ROMÂNIA</span>

        <h1>
          Înțelege lecția.
          <span>În ritmul tău.</span>
        </h1>

        <p>
          OpenEdu te ajută să găsești explicații, exemple și resurse
          educaționale pentru conceptele pe care vrei să le înțelegi mai bine.
        </p>

        <div class="hero-actions">
          <RouterLink to="/resources" class="button button-primary">
            Explorează resursele
          </RouterLink>
        </div>
      </div>

      <div class="learning-card">
        <span class="learning-card-label">Traseul OpenEdu</span>

        <div class="learning-step">
          <span>01</span>
          <strong>Nivel de studiu</strong>
        </div>

        <div class="learning-step">
          <span>02</span>
          <strong>Materie</strong>
        </div>

        <div class="learning-step">
          <span>03</span>
          <strong>Concept / lecție</strong>
        </div>

        <div class="learning-step">
          <span>04</span>
          <strong>Explicații și resurse</strong>
        </div>
      </div>
    </div>

    <div class="feature-grid">
      <article class="feature-card">
        <span class="feature-number">01</span>
        <h2>Explicații diverse</h2>
        <p>
          Același concept poate fi explicat în mai multe moduri,
          astfel încât să găsești abordarea potrivită pentru tine.
        </p>
      </article>

      <article class="feature-card">
        <span class="feature-number">02</span>
        <h2>Conținut validat</h2>
        <p>
          Profesorii contribuie la dezvoltarea și validarea resurselor
          educaționale disponibile în platformă.
        </p>
      </article>

      <article class="feature-card">
        <span class="feature-number">03</span>
        <h2>Fără catalog</h2>
        <p>
          OpenEdu nu înlocuiește școala. Este un spațiu dedicat
          înțelegerii lecțiilor și aprofundării conceptelor.
        </p>
      </article>
    </div>
  </section>
</template>
