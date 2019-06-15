<template>
  <main class="home" v-if="acf">
    <hero-section :acf="acf" :animateHeader="animateHeader"/>
  </main>
</template>

<script>
  import debounce from "lodash/debounce";
  import HeroSection from "@/components/Sections/Home/HeroSection";
  import Config from "~/assets/config";
  import get from "lodash/get";
  import Defer from "@/mixins/Defer";
  let scroller, steps;

  export default {
    async fetch ({ app, store }) {
      if (!store.state.homePage.length) {
        const home = await app.$http.$get(Config.wpDomain + Config.api.homePage);
        store.commit("setHomepage", home);
      }
    },
    mixins: [Defer()],
    data () {
      return {
        animateHeader: false
      };
    },
    components: {
      HeroSection
    },
    head () {
      return { title: "Home" };
    },
    mounted () {
      if (process.client) {
        this.animateHeader = true;
        setTimeout(() => {
          this.handleScroll();
        }, 150);
        if (this.$route.hash) {
          if (process.client) {
            window && window.scrollTo(0, 0);
          }
        }
      }
    },
    methods: {
      hideMenu () {
        this.$store.commit("hideMenuBg");
      },
      showMenu (response) {
        if (
          response.index >= 0 &&
          response.direction === "down" &&
          !this.$store.state.menuScrolled
        ) {
          this.animateWork = true;
          this.$store.dispatch("showMenu");
        }
      },
      handleStepEnter (response) {
        switch (response.index) {
          case 0:
            this.hideMenu();
            this.animateHeader = true;
            break;
          default:
            break;
        }
      },
      handleScroll () {
        if (process.client) {
          const step = document.querySelector(".step");

          if (step && this.defer(5)) {
            if (window.innerWidth > 577) {
              scroller = this.scrollama();
              steps = null;
              steps = scroller
                .setup({
                  step: ".step",
                  offset: 0.5,
                  debug: false
                })
                .onStepEnter(this.handleStepEnter)
                .onStepExit(this.showMenu);

              steps.resize();
              steps.enable();
            } else {
              scroller = this.scrollama();
              steps = null;
              steps = scroller
                .setup({
                  step: ".step",
                  offset: 0.7,
                  debug: false
                })
                .onStepEnter(this.handleStepEnter)
                .onStepExit(this.showMenu);

              steps.resize();
              steps.enable();
            }

            window.addEventListener(
              "resize",
              this.scrollamaResize,
              { passive: true },
              false
            );
          } else {
            setTimeout(() => {
              this.handleScroll();
            }, 600);
          }
        }
      },
      scrollamaResize: debounce(function () {
        const step = document.querySelector(".step");
        if (step && step.length) {
          this.handleScroll();
        }
      }, 150)
    },
    beforeDestroy () {
      if (typeof scroller !== "undefined") {
        scroller.disable && scroller.disable();
      }
      scroller = null;
      steps = null;
      window.removeEventListener("resize", this.scrollamaResize, false);
    },
    computed: {
      scrollama () {
        if (process.browser) {
          let scrollama = require("scrollama");
          return scrollama;
        }
      },
      homePage () {
        if (this.$store.state.homePage == null) return false;
        return this.$store.state.homePage;
      },
      acf () {
        if (this.$store.state.homePage == null) return false;
        return this.homePage.acf;
      }
    }
  };
</script>

<style lang="scss" scoped>
  h1 {
    @include media(sm) {
      max-width: 480px;
      padding-left: 0;
    }
  }

  section {
    margin: $gap / 1.5 0;
    width: 100%;

    @include media(md) {
      margin: $gap * 3 auto;
    }
  }
</style>
