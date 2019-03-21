<template>
  <div class="carousel" v-if="data != null && data.length > 0">
    <div
      @mouseover="hovering = true"
      @mouseleave="hovering = false"
      ref="Carousel"
      v-swiper:blogSwiper="swiperOption"
    >
      <div class="app-carousel swiper-wrapper">
        <div
          class="swiper-slide"
          v-if="item.acf.hero && item.acf.product"
          v-for="item in data"
          :key="item.id"
          @click="$router.push(item.slug)"
        >
          <LazyImage
            class="image"
            :hover="true"
            :image="item.acf.hero.desktop_bg"
            type="'case_study'"
            :title="item.acf.hero.title"
            :imageMobile="item.acf.hero.mobile_bg"
            :link="item.slug"
          >
            <div class="text-section">
              <h3>{{item.acf.hero.title}}</h3>
              <h4 class="subtitle">{{item.acf.category}}</h4>
              <button role="navigation" class="subtitle subtitle--show">Show Case Study</button>
            </div>
          </LazyImage>
        </div>
      </div>
      <!-- slider arrows -->
      <div class="prev">Prev</div>
      <div class="next">Next</div>
    </div>
  </div>
</template>

<script>
  export default {
    name: "TheCarousel",
    props: {
      data: {
        type: Array
      },
      location: {
        type: String,
        default: ""
      }
    },
    data () {
      return {
        sliding: false,
        left: false,
        right: true,
        image: 0,
        hovering: false,
        currentSlide: 0,
        imageModal: false,
        smallCarousel: false,
        interacting: false,
        hoveredNext: false,
        hoveredPrev: false,
        sliderPosition: 0,
        nudged: false,
        nudgedVal: 0,
        swiperOption: {
          initialSlide: 1,
          slidesPerView: "auto",
          centeredSlides: true,
          spaceBetween: 32,
          breakpoints: {
            640: {
              slidesPerView: "auto",
              spaceBetween: 32
            },
            320: {
              slidesPerView: "auto",
              spaceBetween: 16
            }
          },
          autoplay: false,
          loop: false,
          paginationHide: false,
          pagination: ".swiper-pagination"
        }
      };
    },
    mounted () {
      let prev = document.querySelector(".prev");
      prev &&
        prev.addEventListener(
          "click",
          event => {
            event.preventDefault();
            try {
              this.$refs.Carousel.swiper.slidePrev();
            } catch (e) {}
          },
          false
        );

      let next = document.querySelector(".next");
      next &&
        next.addEventListener(
          "click",
          event => {
            event.preventDefault();
            try {
              this.$refs.Carousel.swiper.slideNext();
            } catch (e) {}
          },
          false
        );
    },
    components: {
      LazyImage: () => import("@/components/UI/LazyImage")
    }
  };
</script>

<style lang="scss" scoped>
  .swiper-container {
    height: auto !important;
    background: white;
    min-height: 100%;
    overflow: visible;
    margin-left: auto;
    margin-right: auto;
    padding: 0;
    position: relative;
    .prev,
    .next {
      display: none;
      position: absolute;
      cursor: pointer;
      background-image: unset;
      top: 0;
      right: unset;
      font-size: responsive(18px, 22px);
      font-range: 768px 1200px;
      width: 9vw;
      height: 100%;
      bottom: 0;
      z-index: 100;
      justify-content: center;
      align-items: center;
      color: white;

      @include media(md) {
        display: flex;
      }

      @include media(xl) {
        width: 10vw;
      }
    }
    .prev {
      left: -21px;
      @include media(xl) {
        left: -32px;
      }
      opacity: 1;
      background: linear-gradient(
        to left,
        rgba(0, 0, 0, 0) 0%,
        rgba(0, 0, 0, 0.4) 100%
      );
      transition: opacity 0.3s ease-in-out;
    }
    .next {
      left: unset;
      right: -21px;
      @include media(xl) {
        right: -32px;
      }
      opacity: 1;
      background: linear-gradient(
        to right,
        rgba(0, 0, 0, 0) 0%,
        rgba(0, 0, 0, 0.4) 100%
      );
      transition: opacity 0.3s ease-in-out;
    }
  }
  .swiper-wrapper {
    background: white;
    position: static;

    &.nudged {
      @media (min-width: $desktop) {
        transition: all 1s ease-in-out !important;
      }
    }
    &.hover-animation {
      animation: nudgeLeft 0.6s ease-in-out;
      animation-fill-mode: both !important;
    }
    &.hover-leave {
      animation: nudgeRight 0.6s ease-in-out;
      animation-fill-mode: both !important;
    }
  }
  .inner-carousel {
    position: absolute;
    overflow: hidden;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
  }
  .swiper-slide {
    cursor: pointer;
    overflow: hidden;
    box-shadow: 0 1px 5px 0 $grey;
    height: 300px;
    position: relative;
    width: 65vw !important;
    max-width: 65vw !important;

    @include media(sm) {
      width: 80vw !important;
      max-width: 80vw !important;
      height: 600px;
    }
    .lazy-image {
      pointer-events: none;
    }
    .text-section {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      display: flex;
      flex-direction: column;
      text-align: center;
      opacity: 1;
      z-index: 9999;

      .subtitle {
        opacity: 1;
        transform: translateY(0);
        text-transform: uppercase;

        @include media(lg) {
          opacity: 0;
          transform: translateY(500%);
          transition: all 0.4s ease-in-out;
        }

        &--show {
          box-shadow: 0 0 0 1px white;
          border: none;
          outline: none;
          background: transparent;
          color: white;
          cursor: pointer;
          margin: 0 auto;
          margin-top: 50px;
          display: flex;
          align-items: center;
          line-height: 1;
          justify-content: center;
          height: 50px;
          font-weight: 300;
          transition: all 0.4s ease-in-out;
          width: 150px;
          font-size: 13px;

          @include media(md) {
            width: 220px;
            font-size: $font-size;
          }

          &:hover {
            border: none;
            font-weight: 600;
            box-shadow: 0 0 0 2px white;
          }
        }
      }
    }
    &:hover {
      .text-section .subtitle {
        @include media(lg) {
          opacity: 1;
          transform: translateY(0);
        }
      }
    }
    img {
      height: 300px;
      @media (min-width: $tablet) {
        height: 600px;
      }
      width: 100%;
      object-fit: cover;
      object-position: center;
      backface-visibility: hidden;
      z-index: 0;
      cursor: pointer;
      will-change: transform;
      box-shadow: 0 0 5px 1px white;
      border: 0 solid transparent;
      transition: transform 0.3s ease-in-out;
      &:hover {
        z-index: 1;
        transform: scale(1.05);
      }
    }
    .image {
      position: relative;
      height: 100%;
      .text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 24px;
      }
    }
  }
</style>
