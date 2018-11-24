import Vue from 'vue'
import 'intersection-observer'
import Intersect from 'vue-intersect'
// Vue.use(Intersect); // uses a plugin, but Intersect is a component
Vue.component("intersect", Intersect); // register component <intersect /> globally
