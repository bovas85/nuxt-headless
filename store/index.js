import Vuex from 'vuex'
import Config from '~/assets/config.js'

export const strict = false

export const state = () => ({
  homePage: [],
  window: 320,
  connection: null,
  navOpen: false,
  modalOpen: false,
  menuScrolled: false
})

export const mutations = {
  resetMenus (state) {
    state.modalOpen = false
    state.navOpen = false
  },
  hideMenuBg (state) {
    state.menuScrolled = false
  },
  showMenuBg (state) {
    state.menuScrolled = true
  },
  openMenu (state) {
    if (process.browser) {
      state.navOpen = true
      state.modalOpen = true
      let body = document.querySelector('body')
      if (body) {
        body.style.overflow = 'hidden'
      }
    }
  },
  setHomepage (state, obj) {
    state.homePage = obj
  },
  windowResize (state, size) {
    state.window = size
  },
  setConnection (state, type) {
    state.connection = type
  }
}

export const actions = {
  async nuxtServerInit ({ commit }, { app, route }) {
    // console.log('============= Server Init API calls =============')
    try {
      // const data = app.$axios.$get('someurl') // fetch some data needed for all pages
      // commit('setData', data)
    } catch (e) {
      console.log('error with API', e)
    }
  },
  resetScroll ({ commit }) {
    if (process.browser) {
      let body = document.querySelector('body')
      if (body) {
        body.style.overflow = 'auto'
      }
    }
    commit('resetMenus')
  }
}
