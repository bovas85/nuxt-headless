<template>
  <section v-if="acf" class="section hero step" :style="`background-image: url('${bgImage}')`">
      <div class="container is-flex-column" :class="{'animated': animateHeader}">
        <h1 class="jumbo">{{acf.hero.title}}</h1>
        <h3>{{acf.hero.description}}</h3>
      </div>
      <div v-scroll-to="{element:'.who-i-am'}" class="scroll-down">
        <p>scroll</p>
        <div class="scroll-down__arrow">
          <icon-arrow :fill="'black'" direction='down' name='arrow-down' :width="30" :height="40" />
        </div>
      </div>
    </section>
</template>

<script>
  export default {
    name: 'HeroSection',
    props: ['acf', 'animateHeader', 'bgImage'],
    components: {
      IconArrow: () => import('@/components/Icons/IconArrow')
    }
  }
</script>

<style lang="scss" scoped>
  .hero {
    background-size: cover;
    height: 100vh;
    margin: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    position: relative;
    padding: 0 $gap;
    margin: 0;
    background-position: 75%;

    @include media(sm) {
      padding: 0;
      background-position: 85%;
    }

    @include media(md) {
      margin: 0 auto $gap * 3;
    }

    @include media(lg) {
      background-position: center;
    }

    .scroll-down {
      position: absolute;
      bottom: 80px;
      left: 60px;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: black;
      cursor: pointer;
      display: none;

      @include media(sm) {
        display: flex;
      }

      p {
        font-size: 22px;
        line-height: 1;
        margin-bottom: $gap;
        writing-mode: vertical-rl;
      }
    }

    h1 {
      padding: 0 0 $gap;

      @include media(sm) {
        max-width: 410px;
        padding: 0 0 $gap;
      }
    }

    h1,
    h3 {
      color: white;
    }

    .container {
      @include fadeInUp;
    }
  }
</style>
