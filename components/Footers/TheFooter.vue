<template>
  <footer class="footer">
    <div class="container is-flex">
      <div class="left-section is-flex-column">
        <img src="https://placehold.it/40/40" alt="">
        <div class="links">
          <p>Email: <a href="mailto:hello@email.com?subject=Interview request">hello@email.com</a></p>
          <span><nuxt-link to="#">Privacy Policy</nuxt-link></span>
          <p class="referral-link">Coded with 🧔 by <a href="https://moustachedesign.xyz" target="_blank">Moustache Design</a></p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
  export default {
    name: 'TheFooter',
    props: {
      mobilenav: {
        type: Boolean,
        default: true
      }
    }
  }
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
        margin-top: $gap / 2.5;

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
