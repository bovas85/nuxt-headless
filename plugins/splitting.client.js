import Vue from 'vue'
import Splitting from 'splitting'

Vue.use(Splitting)

export default ({ app }, inject) => {
  inject('Splitting', Splitting)
}
