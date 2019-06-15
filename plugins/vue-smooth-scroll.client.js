import Vue from 'vue'
import VueScrollTo from 'vue-scrollto'

Vue.use(VueScrollTo)

export default ({ app }, inject) => {
  inject('VueScrollTo', VueScrollTo)
}
