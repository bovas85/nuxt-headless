<template>
  <section v-if="acf" class="section hero step">
    <LazyImage
      class="image"
      :image="acf.hero.desktop_bg"
      :title="acf.hero.title"
      :hover="false"
      positionMobile="left"
      :imageMobile="acf.hero.mobile_bg"
      lazyload
      svg
      home
    />
    <div class="container is-flex-column" :class="{ animated: animateHeader }">
      <h1 class="jumbo">{{ acf.hero.title }}</h1>
      <h3>{{ acf.hero.description }}</h3>
    </div>
  </section>
</template>

<script>
import LazyImage from "@/components/UI/LazyImage";
export default {
  name: "HeroSection",
  props: ["acf", "animateHeader"],
  components: {
    IconArrow: () => import("@/components/Icons/IconArrow"),
    LazyImage
  }
};
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
  background-color: $yellow;

  .image {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
  }

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

  .rotating-text {
    position: absolute;
    cursor: pointer;
    display: flex;
    bottom: $gap * 2.5;
    left: $gap;
    width: 70px;
    height: 70px;

    @include media(sm) {
      bottom: 80px;
      right: 60px;
      left: auto;
      width: 100px;
      height: 100px;
      transform-origin: center;
      animation: rotate 15s linear infinite;
    }

    img {
      object-fit: cover;
      width: 100%;
      height: 100%;
    }

    @keyframes rotate {
      100% {
        transform: rotate(360deg);
      }
    }
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
