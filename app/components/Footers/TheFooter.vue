<template>
  <footer class="footer">
    <div class="container is-flex">
      <div class="left-section is-flex-column">
        <img src="https://placehold.co/40x40" alt="Logo" />
        <div class="links">
          <p>
            Email:
            <a href="mailto:hello@email.com?subject=Interview request">hello@email.com</a>
          </p>
          <span><NuxtLink to="#">Privacy Policy</NuxtLink></span>
          <p class="referral-link">
            Coded with ❤️ by
            <a href="https://moustachedesign.fyi" target="_blank" rel="noopener noreferrer">Moustache Design</a>
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    mobilenav?: boolean
  }>(),
  {
    mobilenav: true
  }
)
</script>

<style lang="scss" scoped>
footer {
  background-color: $grey;
  color: white;
  padding: $gap;

  .container {
    align-items: space-between;
    justify-content: flex-start;
    flex-direction: column;

    @include media(sm) {
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
    }
  }

  @include media(sm) {
    padding: $gap 0;
  }

  img {
    object-fit: contain;
    width: 60px;
    height: 60px;
  }

  .links {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-around;

    a {
      color: white;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease-in-out;

      &:hover {
        color: $primary;
      }
    }

    p {
      line-height: 1.5;
      margin-top: calc($gap / 2.5);

      &.referral-link {
        font-size: $font-size;
      }
    }
  }

  .left-section {
    flex-basis: 100%;

    @include media(sm) {
      flex-basis: unset;
    }
  }
}
</style>
