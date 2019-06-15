<template>
  <svg :class="{'animating': animating, 'static': static}" xmlns="http://www.w3.org/2000/svg" :viewBox="computedViewBox" :width="computedWidth" :height="computedHeight"><title>{{title}}</title><g class="nc-icon-wrapper"><path class="path" d="M0 .059l6.535.15v1.972l-3.65.336V27.97l3.65.335v1.98L0 30.436zm14.245 9.454c.73-.637 3.13-2.458 5.654-2.458 3.34 0 4.858 2.123 4.858 5.47v8.507l2.282.763v1.67h-7.29v-1.67l1.97-.763v-8.205c0-2.122-.763-3.188-2.734-3.188-2.584 0-4.404 2.433-4.404 2.433v8.96l1.971.763v1.67H8.96v-1.67l2.584-.763V9.79L8.96 9.186V7.508l5.008-.15.277 2.155zm17.559 11.695s.914.277 2.55.277c1.82 0 3.431-.52 3.431-2.4 0-3.28-8.473-1.761-8.473-7.29 0-3.162 2.433-4.74 5.562-4.74 2.4 0 5.193.915 5.193.915l-.092 4.102h-1.972l-.486-2.366s-.94-.36-2.458-.36c-1.552 0-2.702.486-2.702 2.038 0 3.247 8.474 2.005 8.474 7.198 0 3.49-3.037 5.193-6.687 5.193-2.793 0-4.798-.671-4.798-.671v-4.497h2.122l.336 2.601zm15.251 6.77V2.525l-3.649-.335V.218l6.535-.15v30.377l-6.535-.151v-1.972z" /></g></svg>
</template>

<script>
  export default {
    name: 'TheLogoStatic',
    props: {
      animating: {
        type: Boolean,
        default: false
      },
      width: {
        type: [Number, String],
        default: 80
      },
      mobileWidth: {
        type: [Number, String],
        default: 50
      },
      title: {
        type: String,
        default: 'Logo'
      },
      height: {
        type: [Number, String],
        default: 51
      },
      mobileHeight: {
        type: [Number, String],
        default: 48
      },
      fill: {
        type: String,
        default: 'currentColor'
      },
      static: {
        type: Boolean,
        default: false
      }
    },
    computed: {
      computedViewBox () {
        if (this.$store.state.window < 577) {
          return false
        } else {
          return `0 0 ${this.computedWidth / 1.8} ${this.computedHeight / 1.5}`
        }
      },
      computedWidth () {
        if (this.$store.state.window < 577) {
          return this.mobileWidth
        } else return this.width
      },
      computedHeight () {
        if (this.$store.state.window < 577) {
          return this.mobileHeight
        } else return this.height
      }
    }
  }
</script>

<style lang="scss" scoped>
  svg {
    display: inline-block;
    vertical-align: baseline;
    margin-bottom: -2px;
    position: absolute;
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateY(-50%);

    &.static {
      position: static;
      transform: none;
    }

    path {
      fill: #fa7921;
      stroke: none;
      opacity: 1;
      z-index: 0;
    }
    @include media(md) {
      &.animating {
        .path {
          animation: viLogoAnim 1.9s linear forwards;
        }
      }
    }
  }
</style>
