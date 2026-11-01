<template>
  <main class="privacy-page container">
    <div class="content">
      <h1>Privacy Policy</h1>
      <p class="updated">Last updated: November 2026</p>

      <section>
        <h2>1. Overview</h2>
        <p>
          This website is a demonstration boilerplate showcasing a decoupled, headless architecture using Nuxt 4 and the WordPress REST API.
        </p>
      </section>

      <section>
        <h2>2. Cookies</h2>
        <p>
          We use minimal local cookies solely to remember your cookie preferences and interface state. We do not use invasive tracking cookies or sell your personal information.
        </p>
      </section>

      <section>
        <h2>3. Data Collection &amp; Contact Forms</h2>
        <p>
          Any information submitted via the contact or newsletter forms is transmitted securely and used solely to respond to your inquiry.
        </p>
      </section>

      <section>
        <NuxtLink to="/" class="button is-primary">← Back to Home</NuxtLink>
      </section>
    </div>
  </main>
</template>

<script setup lang="ts">
useHead({
  title: 'Privacy Policy'
})
</script>

<style lang="scss" scoped>
.privacy-page {
  min-height: 80vh;
  padding: calc($gap * 4) $gap ($gap * 2);

  @include media(sm) {
    padding: calc($gap * 5) 0 ($gap * 2);
  }

  .content {
    max-width: 720px;
    margin: 0 auto;

    h1 {
      font-size: clamp(2rem, 4vw, 3rem);
      margin-bottom: calc($gap / 2);
      font-weight: 700;
    }

    .updated {
      color: $grey;
      margin-bottom: $gap;
      font-size: 0.95rem;
    }

    section {
      margin-bottom: $gap * 1.5;

      h2 {
        font-size: 1.4rem;
        margin-bottom: calc($gap / 3);
        font-weight: 600;
      }

      p {
        line-height: 1.7;
        color: #333;
      }
    }

    .button {
      margin-top: $gap;
      display: inline-block;
      padding: 10px 20px;
      background-color: $primary;
      color: black;
      font-weight: 600;
      border-radius: 4px;
      text-decoration: none;
      transition: opacity 0.2s ease;

      &:hover {
        opacity: 0.85;
      }
    }
  }
}
</style>
