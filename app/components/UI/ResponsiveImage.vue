<template>
  <div
    v-if="sources != null && imageSrcset"
    class="responsive-img"
    :class="{ 'disabled-hover': !hover, home }"
  >
    <img
      :srcset="imageSrcset"
      :alt="sources.alt || 'Responsive Image'"
      sizes="(max-width: 576px) 400px,
             (max-width: 1100px) 1024px,
             (min-width: 1101px) 1100px,
             (min-width: 1921px) 2048px"
      :src="sources.sizes?.thumbnail || sources.sizes?.medium || '/icon.png'"
      loading="lazy"
    />
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    sources?: Record<string, any>
    hover?: boolean
    home?: boolean
  }>(),
  {
    sources: () => ({}),
    hover: true,
    home: false
  }
)

const imageSrcset = computed(() => {
  const s = props.sources?.sizes
  if (!s) return ''
  return `${s.small || '/icon.png'} 500w,
          ${s.medium || '/icon.png'} 1024w,
          ${s.large || '/icon.png'} 1920w,
          ${s.ultra || '/icon.png'} 2048w`
})
</script>

<style lang="scss" scoped>
.responsive-img {
  width: 100%;
  height: 100%;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.6s ease-in-out;

  img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: contain;
    object-position: center;
    transition: all 0.6s ease-in-out;
  }

  &:hover:not(.disabled-hover) img {
    transform: scale(1.05);
  }

  &.home img {
    object-position: right;
    @media (min-width: $tablet) {
      animation: zoomIn 60s infinite;
    }
  }

  &.disabled-hover {
    cursor: auto;
    pointer-events: none;
  }
}

@keyframes zoomIn {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}
</style>
