<template>
  <div class="page-not-found">
    <div class="container is-flex">
      <nuxt-link class="button button--sent" to="/">HOME</nuxt-link>
    </div>
  </div>
</template>

<script>
  export default {
    head () {
      return {
        title: "Page not Found"
      };
    }
  };
</script>

<style lang="scss" scoped>
  .page-not-found {
    height: 100vh;
    height: calc(100vh - 180px); // footer visible
    background-color: $yellow;
    background-repeat: no-repeat;
    background-size: 80%;
    background-position: center;

    @include media(sm) {
      background-size: 70%;
    }

    .container {
      height: 100%;
      align-items: flex-end;
      justify-content: center;
      text-align: center;

      a {
        margin-bottom: 17vh;
      }
    }
  }
</style>


