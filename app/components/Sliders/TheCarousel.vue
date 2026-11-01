<template>
  <div v-if="data != null && data.length > 0" class="carousel">
    <div
      ref="carouselRef"
      class="swiper"
      @mouseover="hovering = true"
      @mouseleave="hovering = false"
    >
      <div class="app-carousel swiper-wrapper">
        <div
          v-for="item in data"
          :key="item.id"
          class="swiper-slide"
          @click="item.slug && router.push(item.slug)"
        >
          <AppImage
            class="image"
            :hover="true"
            :image="item.acf?.hero?.desktop_bg"
            :title="item.acf?.hero?.title"
            :image-mobile="item.acf?.hero?.mobile_bg"
            :link="item.slug"
          >
            <div class="text-section">
              <h3>{{ item.acf?.hero?.title }}</h3>
              <h4 class="subtitle">{{ item.acf?.category }}</h4>
              <button
                role="navigation"
                aria-label="Show case study"
                class="subtitle subtitle--show"
              >
                Show Case Study
              </button>
            </div>
          </AppImage>
        </div>
      </div>
      <!-- Slider Arrows -->
      <div class="prev" @click="slidePrev">Prev</div>
      <div class="next" @click="slideNext">Next</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Swiper from 'swiper'
import 'swiper/css'

withDefaults(
  defineProps<{
    data?: any[]
    location?: string
  }>(),
  {
    data: () => [],
    location: ''
  }
)

const router = useRouter()
const carouselRef = ref<HTMLElement | null>(null)
const swiperInstance = shallowRef<Swiper | null>(null)
const hovering = ref(false)

const slidePrev = () => {
  swiperInstance.value?.slidePrev()
}

const slideNext = () => {
  swiperInstance.value?.slideNext()
}

onMounted(() => {
  if (carouselRef.value) {
    swiperInstance.value = new Swiper(carouselRef.value, {
      initialSlide: 1,
      slidesPerView: 'auto',
      centeredSlides: true,
      spaceBetween: 32,
      breakpoints: {
        640: {
          slidesPerView: 'auto',
          spaceBetween: 32
        },
        320: {
          slidesPerView: 'auto',
          spaceBetween: 16
        }
      }
    })
  }
})

onBeforeUnmount(() => {
  swiperInstance.value?.destroy()
})
</script>

<style lang="scss" scoped>
.swiper {
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
    z-index: 99;

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
    cursor: pointer;
    box-shadow: 0 0 5px 1px white;
    border: 0 solid transparent;
    transition: transform 0.3s ease-in-out;
    &:hover {
      transform: scale(1.05);
    }
  }

  .image {
    position: relative;
    height: 100%;
  }
}
</style>
