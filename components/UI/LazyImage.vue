<template>
  <div
    v-if="image.url != null && imageMobile.url != null"
    class="lazy-image"
    :class="[{'hover-disabled': !hover, 'contain': contain}, computedClass]"
    :style="!this.loaded && !noBg ? `background-color: #f4a261`: null"
  >
    <no-ssr>
      <vue-media :query="{maxWidth: 576}">
        <progressive-img
          :src="imageMobile.sizes.medium"
          :alt="imageMobile.alt"
          @onLoad.once="imageLoaded"
          @onError="capture($event)"
          :placeholder="thumbnail"
          no-ratio
          :blur="15"
        />
      </vue-media>
    </no-ssr>
    <no-ssr>
      <vue-media :query="({minWidth: 577, maxWidth: 1200})">
        <progressive-img
          :src="image.sizes.large"
          :alt="image.alt"
          @onLoad.once="imageLoaded"
          @onError="capture($event)"
          :placeholder="thumbnail"
          no-ratio
          :blur="15"
        />
      </vue-media>
    </no-ssr>
    <no-ssr>
      <vue-media :query="({minWidth: 1201, maxWidth: 1920})">
        <progressive-img
          :src="getImage ? getImage : image.sizes.ultra"
          :alt="image.alt"
          @onLoad.once="imageLoaded"
          @onError="capture($event)"
          :placeholder="thumbnail"
          no-ratio
          :blur="15"
        />
      </vue-media>
    </no-ssr>
    <no-ssr>
      <vue-media :query="{minWidth: 1921}">
        <progressive-img
          :src="getImage ? getImage : image.sizes['4k']"
          :alt="image.alt"
          @onLoad.once="imageLoaded"
          @onError="capture($event)"
          :placeholder="thumbnail"
          no-ratio
          :blur="15"
        />
      </vue-media>
    </no-ssr>
    <div
      v-show="onHover || type !== 'case_study'"
      class="text-container"
      :class="{'on-hover': onHover, 'mobile-visible': hoverFixed}"
    >
      <nuxt-link v-if="link && title" :to="type === 'case_study' ? `/${link}` : link" class="text"></nuxt-link>
    </div>
    <slot></slot>
  </div>
</template>

<script>
  export default {
    name: "LazyImage",
    props: {
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
      .progressive-image-main {
        background: transparent;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: auto;
      }
    }
    .work-navigation {
      .progressive-image-wrapper {
        padding-top: 0 !important;
      }
    }
  }

  @supports (display: grid) {
    .progressive-image,
    .progressive-image-wrapper {
      position: static;
      height: 100%;
      object-fit: cover;
      padding-top: unset;
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
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: left;
        }
      }
    }
    &.right {
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: right;
        }
      }
    }
    &.bottom {
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: bottom;
        }
      }
    }
    &.top {
      .progressive-image,
      .progressive-image-wrapper {
        .progressive-image-main {
          object-position: top;
        }
      }
    }
    &.contain {
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
    .progressive-image-main {
      background: transparent;
      height: 100% !important;
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