<template>
  <div
    v-if="image.url != null && imageMobile.url != null"
    class="lazy-image"
    :class="[{'hover-disabled': !hover, 'contain': contain}, computedClass]"
  >
    <no-ssr>
      <vue-media :query="{maxWidth: 576}">
        <picture>
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="`${imageMobile.sizes.medium}${svg ? '' : '.webp'}`"
            type="image/webp"
            :alt="imageMobile.alt"
          >
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="`${imageMobile.sizes.medium}`"
            :alt="imageMobile.alt"
          >
          <img
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            src='/images/Homepage.svg'
            :data-src="imageMobile.sizes.medium"
            :alt="imageMobile.alt"
          />
        </picture>
      </vue-media>
    </no-ssr>
    <no-ssr>
      <vue-media :query="({minWidth: 577, maxWidth: 1200})">
        <picture>
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="`${image.sizes.large}${svg ? '' : '.webp'}`"
            type="image/webp"
            :alt="image.alt"
          >
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="`${image.sizes.large}`"
            :alt="image.alt"
          >
          <img
            :class='lazyload ? "lazyload": ""'
            src='/images/Homepage.svg'
            :data-src="image.sizes.large"
            :alt="image.alt"
          />
        </picture>
      </vue-media>
    </no-ssr>
    <no-ssr>
      <vue-media :query="({minWidth: 1201, maxWidth: 1920})">
        <picture>
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="getImage ? `${getImage}${svg ? '' : '.webp'}` : `${image.sizes.ultra}${svg ? '' : '.webp'}`"
            type="image/webp"
            :alt="image.alt"
          >
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="getImage ? `${getImage}` : `${image.sizes.ultra}`"
            :alt="image.alt"
          >
          <img
            :class='lazyload ? "lazyload": ""'
            src='/images/Homepage.svg'
            :data-src="getImage ? getImage : image.sizes.ultra"
            :alt="image.alt"
          />
        </picture>
      </vue-media>
    </no-ssr>
    <no-ssr>
      <vue-media :query="{minWidth: 1921}">
        <picture>
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="getImage ? `${getImage}${svg ? '' : '.webp'}` : `${image.sizes['4k']}${svg ? '' : '.webp'}`"
            type="image/webp"
            :alt="image.alt"
          >
          <source
            :class='lazyload ? "lazyload": ""'
            :loading='lazyload ? "lazy": ""'
            srcset='/images/Homepage.svg'
            :data-srcset="getImage ? `${getImage}` : `${image.sizes['4k']}`"
            :alt="image.alt"
          >
          <img
            :class='lazyload ? "lazyload": ""'
            src='/images/Homepage.svg'
            :data-src="getImage ? getImage : image.sizes['4k']"
            :alt="image.alt"
          />
        </picture>
      </vue-media>
    </no-ssr>
    <slot></slot>
  </div>
</template>

<script>
  export default {
    name: "LazyImage",
    props: {
      svg: {
        type: Boolean,
        default: false
      },
      lazyload: {
        type: Boolean,
        default: true,
      },
      image: {
        type: [Object, Boolean]
      },
      imageMobile: {
        type: [Object, Boolean]
      },
      title: "",
      link: {
        default: false
      },
      noPlaceholder: {
        type: Boolean
      },
      type: {
        type: String,
        default: ""
      },
      home: {
        type: Boolean,
        default: false
      },
      noBg: {
        type: Boolean,
        default: false
      },
      isThumb: {
        type: Boolean,
        default: false
      },
      position: {
        type: String,
        default: "center"
      },
      positionMobile: {
        type: String,
        default: "center"
      },
      contain: {
        type: Boolean,
        default: false
      },
      hover: {
        default: true
      },
      onHover: {
        type: Boolean,
        default: false
      },
      hoverFixed: {
        type: Boolean,
        default: false
      }
    },
    data () {
      return {
        loaded: false
      };
    },
    methods: {
      capture (event) {
        return false;
      },
      imageLoaded (event) {
        this.loaded = true;
      }
    },
    computed: {
      computedClass () {
        if (this.$store.state.window < 577) return this.positionMobile;
        else return this.position;
      },
      getImage () {
        if (this.isThumb) {
          return this.image.sizes.small;
        } else if (
          this.$store.state.connection === "cellular" ||
          this.$store.state.connection === "other"
        ) {
          return this.image.sizes.medium;
        } else return false;
      },

      thumbnail () {
        if (this.noPlaceHolder) {
          return false;
        }
        if (this.image != null) {
          return this.image.sizes.thumbnail;
        } else return "https://placehold.it/150x150";
      }
    }
  };
</script>

<style lang="scss">
  @supports not (display: grid) {
    .progressive-image-wrapper {
      position: relative;
      padding-top: 56.25%; /* 16:9 Aspect Ratio */
      img,
      .progressive-image-main {
        background: transparent;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
      }
    }
    .work-navigation {
      .progressive-image-wrapper {
        padding-top: 0 !important;
      }
    }
  }

  @supports (display: grid) {
    img,
    .progressive-image,
    .progressive-image-wrapper {
      position: static;
      height: 100%;
      object-fit: cover;
      width: 100%;
      height: 100%;
      padding-top: unset;
      img,
      .progressive-image-main {
        height: 100%;
        position: relative;
        object-fit: cover;
        z-index: -1;
        width: 100%;
        height: 100%;
      }
      .progressive-image-placeholder {
        background-size: cover;
        background-position: center;
        width: auto;
      }
      .progressive-image-wrapper {
        overflow: hidden;
      }
    }
  }
  
  .lazy-image {
    height: 100%;
    width: 100%;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    position: relative;

    &:before {
      display: none;
    }
    .text-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      opacity: 1;
      text-align: center;
      z-index: 101;
      transition: all 0.3s ease-in-out;
      &.on-hover {
        justify-content: center !important;
        padding: 0 !important;
        opacity: 0;
        &.mobile-visible {
          opacity: 1;
        }
      }
      @media (max-width: $mobile) {
        opacity: 1 !important;
      }
      .text {
        font-size: 52px;
        line-height: 1;
        text-align: center;
        pointer-events: none;
        text-transform: uppercase;
        font-weight: 300;
        // padding: 0 32px;
        text-decoration: none;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0;
      }
      &:hover {
        opacity: 1;
        color: white;
      }
    }
    &:hover {
      .text-container {
        opacity: 1;
      }
      // &:before {
      //   z-index: 100;
      //   background-color: rgba(0, 0, 0, 0.25);
      // }
      img {
        transform: scale(1.05);
      }
    }
    &.hover-disabled {
      cursor: auto;
      &:before {
        transition: all 0.3s ease-in-out;
      }
      &:hover {
        .text-container {
          opacity: 1;
        }
        &:before {
          display: none;
        }
        img {
          transform: scale(1);
        }
      }
    }
    &.home {
      background-size: cover;
      background-repeat: no-repeat;
      pointer-events: none;
      &:hover {
        pointer-events: none;
        .text-container {
          opacity: 0;
        }
      }
      img {
        object-position: right;
        @media (min-width: $tablet) {
          animation: zoomImage infinite;
          animation-delay: 0.3s;
          animation-timing-function: linear;
          animation-fill-mode: both;
          animation-duration: 80s;
          backface-visibility: hidden;
          &:hover {
            .overlay {
              opacity: 0;
            }
            img {
              transform: none;
            }
          }
        }
      }
    }
    img {
      transition: transform 0.6s ease-in-out;
    }
    &.left {
      img,
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: left;
        }
      }
    }
    &.right {
      img,
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: right;
        }
      }
    }
    &.bottom {
      img,
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: bottom;
        }
      }
    }
    &.top {
      img,
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: top;
        }
      }
    }
    &.contain {
      img,
      .progressive-image,
      .progressive-image-wrapper {
        object-fit: contain;
        .progressive-image-main {
          object-fit: contain;
        }
      }
    }
  }
  .bg-image--second {
    img,
    .progressive-image-main {
      background: transparent;
      height: 100%;
      width: 100%;
      object-fit: cover;
      margin-top: 0;
      margin-bottom: -5px;
      object-position: 0 0;
      @media (min-width: $tablet) {
        object-fit: cover;
      }
    }
  }
  @keyframes zoomImage {
    0% {
      opacity: 1;
      transform: scale3d(1, 1, 1);
    }
    50% {
      opacity: 1;
      transform: scale3d(1.4, 1.4, 1.4);
    }
    100% {
      opacity: 1;
      transform: scale3d(1, 1, 1);
    }
  }
</style>