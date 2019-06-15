<template functional>
  <svg xmlns="http://www.w3.org/2000/svg" :width="props.width" :height="props.height">
    <g fill="none" fill-rule="evenodd">
      <path :stroke="props.stroke" stroke-linecap="square" stroke-width="2" d="M1 1h34M1 20h54"></path>
      <path
        :fill="props.fill"
        d="M8.184 6.456h.996V15H8.064V9.132L5.532 15H4.68L2.112 9.168 2.124 15H1.008V6.456h.996l3.108 7.056 3.072-7.056zm3 8.544V6.456h5.544v1.008h-4.32v2.688h4.08v1.008h-4.08v2.832h4.32V15h-5.544zM24.12 6.456h1.164V15h-.948L19.44 8.544V15h-1.164V6.456h.936l4.908 6.468V6.456zm6.576 8.64c-1.1360057 0-1.9979971-.295997-2.586-.888-.5880029-.592003-.882-1.4599943-.882-2.604V6.456h1.212v5.232c0 .792004.1899981 1.389998.57 1.794.3800019.404002.9419963.606 1.686.606.7360037 0 1.2939981-.203998 1.674-.612.3800019-.408002.57-1.0039961.57-1.788V6.456h1.224v5.148c0 1.1440057-.2939971 2.011997-.882 2.604-.5880029.592003-1.4499943.888-2.586.888z"
      ></path>
    </g>
  </svg>
</template>

<script>
  export default {
    name: "BurgerMenu",
    props: {
      fill: {
        type: String,
        default: "black"
      },
      stroke: {
        type: String,
        default: "black"
      },
      width: {
        type: Number,
        default: 36
      },
      height: {
        type: Number,
        default: 21
      }
    }
  };
</script>
