import Vue from 'vue'

// disable dev mode message in console
Vue.config.productionTip = false

Vue.mixin({
  methods: {
    capitalizeFirstLetter (string) {
      return string.charAt(0).toUpperCase() + string.slice(1)
    }
  }
})
