<template>
    <div :class="direction" class="svg">
        <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" viewBox="0 0 100 100" :width="width" :height="height">
          <path :style="{'color': fill}" :fill="fill" :stroke="fill" d="M50 92l16-29.1M50 92L34 62.9" class="st0"/>
        </svg>
    </div>
</template>

<script>
  export default {
    name: 'IconArrow',
    props: {
      fill: {
        type: String,
        default: 'white'
      },
      width: {
        type: Number,
        default: 40
      },
      height: {
        type: Number,
        default: 40
      },
      direction: {
        type: String,
        validator: value => {
          // The value must match one of these strings
          return ['left', 'right', 'up', 'down'].indexOf(value) !== -1
        },
        default: 'down'
      },
      name: {
        type: String,
        default: 'arrow'
      }
    }
  }
</script>

<style scoped lang="scss">
  path {
    stroke-width: 3.9036;
    stroke-linecap: round;
    stroke-linejoin: round;
  }
  .svg {
    position: relative;
    display: inline-block;
    text-align: right;

    &:before {
      content: '';
      position: absolute;
      bottom: 12px;
      display: block;
      z-index: 100;
      width: 2px;
      right: 48%;
      height: 30px;
      margin-top: -1px;
      color: currentColor;
      background-color: currentColor;
      transition: all 0.3s ease-in-out;
    }

    &:hover {
      &:before {
        height: 40px;
      }
    }
  }
  .left,
  .right,
  .bottom,
  .up {
    transform-origin: center;
  }
  .left {
    transform: rotate(90deg);
  }
  .right {
    transform: rotate(-90deg);
  }
  .down {
    transform: rotate(0deg);
  }
  .up {
    transform: rotate(180deg);
  }
</style>
