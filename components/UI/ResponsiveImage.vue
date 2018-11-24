<template>
  <div 
    v-if="sources != null && image.length" 
    class="responsive-img" 
    :class="{'disabled-hover': !hover, 'home': home}"
  >
    <img 
      :srcset="image"
      :alt="sources.alt"
      sizes="(max-width: 576px) 400px,
             (max-width: 1100px) 1024px,
             (min-width: 1101px) 1100px,
             (min-width: 1921px) 2048px"
      :src="sources.sizes.thumbnail"
    />
    <!-- <div class="overlay"></div> -->
  </div>
</template>

<script>
  export default {
    name: "ResponsiveImage",
    props: {
      sources: {
        default: () => {}
      },
      hover: {
        type: Boolean,
        default: true
      },
      home: {
        type: Boolean,
        default: false
      },
      addclass: {}
    },
    computed: {
      image () {
        if (this.$store.state.connect === "other") {
          return `${this.sources.small} 500w,
                            ${this.sources.sizes.small} 1024w,
                            ${this.sources.sizes.medium} 1920w,
                            ${this.sources.sizes.ultra} 2048w`;
        } else if (this.$store.state.connect === "cellular") {
          return `${this.sources.small} 500w,
                            ${this.sources.sizes.medium} 1024w,
                            ${this.sources.sizes.medium} 1920w,
                            ${this.sources.sizes.ultra} 2048w`;
        } else {
          if (this.sources != null) {
            return `${this.sources.sizes.small} 500w,
                              ${this.sources.sizes.medium} 1024w,
                              ${this.sources.sizes.large} 1920w,
                              ${this.sources.sizes.ultra} 2048w`;
          } else {
            return `https://placehold.it/150x150 500w,
                              https://placehold.it/800x600 1024w,
                              https://placehold.it/1200x720 1100w,
                              https://placehold.it/2048x1080 2048w`;
          }
        }
      }
    }
  };
</script>

<style lang="scss" scoped>
  .responsive-img {
    width: 100%;
    height: 100%;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.6s ease-in-out;
    .overlay {
      // position: absolute;
      // top: 0;
      // left: 0;
      // pointer-events: append;
      // cursor: auto;
      // right: 0;
      // bottom: 0;
      // opacity: 0;
      // background: rgba(0, 0, 0, 0.5);
      // transition: all 0.6s ease-in-out;
      display: none;
    }
    img {
      transition: all 0.6s ease-in-out;
    }
    &:hover {
      .overlay {
        opacity: 1;
      }
      img {
        transform: scale(1.05);
      }
    }
    &.home {
      .overlay {
        opacity: 0.5;
      }
      img {
        object-position: right;
      }
      @media (min-width: $tablet) {
        animation: zoomIn infinite;
        animation-duration: 60s;
        backface-visibility: hidden;
        transform: translateZ(0);
        perspective: 1000px;
        will-change: transform;
        transition: opacity 1.3s;
        &:hover {
          .overlay {
            opacity: 0.5;
          }
          img {
            transform: none;
          }
        }
      }
    }
    &.disabled-hover {
      // animation: zoomIn 60s infinite;
      // backface-visibility: hidden;
      // transform: translateZ(0);
      // perspective: 1000px;
      // will-change: transform;
      cursor: auto;
      pointer-events: none;
      &:hover {
        .overlay {
          opacity: 0 !important;
        }
        img {
          transform: none !important;
        }
      }
    }
    img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: contain;
      object-position: center;
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
  }
  .featured-img img {
    height: 100%;
    object-fit: cover;
    object-position: center;
  }
</style>
