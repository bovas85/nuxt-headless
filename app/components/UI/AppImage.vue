<template>
  <div
    v-if="image?.url != null || imageMobile?.url != null"
    class="lazy-image"
    :class="[{ 'hover-disabled': !hover, contain, home }, computedPosition]"
  >
    <picture>
      <source
        v-if="imageMobile?.sizes?.medium"
        media="(max-width: 576px)"
        :srcset="imageMobile.sizes.medium"
      />
      <source
        v-if="image?.sizes?.large"
        media="(max-width: 1200px)"
        :srcset="image.sizes.large"
      />
      <source
        v-if="image?.sizes?.ultra"
        media="(max-width: 1920px)"
        :srcset="image.sizes.ultra"
      />
      <source
        v-if="image?.sizes?.['4k']"
        media="(min-width: 1921px)"
        :srcset="image.sizes['4k']"
      />
      <img
        :src="image?.sizes?.large || image?.url || imageMobile?.url || '/icon.png'"
        :alt="title || image?.alt || imageMobile?.alt || 'Image'"
        :loading="lazyload ? 'lazy' : 'eager'"
        @load="loaded = true"
      />
    </picture>
    <slot />
  </div>
</template>

<script setup lang="ts">
interface ImageObject {
  url?: string
  alt?: string
  sizes?: {
    medium?: string
    large?: string
    ultra?: string
    '4k'?: string
    thumbnail?: string
    [key: string]: string | undefined
  }
}

const props = withDefaults(
  defineProps<{
    svg?: boolean
    lazyload?: boolean
    image?: ImageObject | null
    imageMobile?: ImageObject | null
    title?: string
    link?: string | false
    position?: string
    positionMobile?: string
    contain?: boolean
    hover?: boolean
    home?: boolean
  }>(),
  {
    svg: false,
    lazyload: true,
    image: null,
    imageMobile: null,
    title: '',
    link: false,
    position: 'center',
    positionMobile: 'center',
    contain: false,
    hover: true,
    home: false
  }
)

const appStore = useAppStore()
const loaded = ref(false)

const computedPosition = computed(() => {
  return appStore.windowWidth < 577 ? props.positionMobile : props.position
})
</script>

<style lang="scss">
.lazy-image {
  height: 100%;
  width: 100%;
  position: relative;

  picture {
    display: block;
    width: 100%;
    height: 100%;
  }

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.6s ease-in-out;
  }

  &.contain img {
    object-fit: contain;
  }

  &.left img {
    object-position: left;
  }

  &.right img {
    object-position: right;
  }

  &.top img {
    object-position: top;
  }

  &.bottom img {
    object-position: bottom;
  }

  &:hover:not(.hover-disabled) img {
    transform: scale(1.05);
  }

  &.home img {
    object-position: right;
    @media (min-width: $tablet) {
      animation: zoomImage 80s linear infinite;
    }
  }
}

@keyframes zoomImage {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.4);
  }
  100% {
    transform: scale(1);
  }
}
</style>