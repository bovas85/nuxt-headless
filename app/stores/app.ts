import { defineStore } from 'pinia'

export interface AppState {
  homePage: Record<string, any> | null
  windowWidth: number
  connection: string | null
  navOpen: boolean
  modalOpen: boolean
  menuScrolled: boolean
  menuScrolledDone: boolean
}

export const useAppStore = defineStore('app', {
  state: (): AppState => ({
    homePage: null,
    windowWidth: 320,
    connection: null,
    navOpen: false,
    modalOpen: false,
    menuScrolled: false,
    menuScrolledDone: false
  }),

  actions: {
    resetMenus() {
      this.modalOpen = false
      this.navOpen = false
      if (import.meta.client) {
        document.body.style.overflow = 'auto'
      }
    },

    hideMenuBg() {
      this.menuScrolled = false
      this.menuScrolledDone = false
    },

    showMenuBg() {
      this.menuScrolled = true
    },

    showMenuBgDone() {
      this.menuScrolledDone = true
    },

    async showMenu() {
      setTimeout(() => {
        this.showMenuBg()
      }, 200)
      setTimeout(() => {
        this.showMenuBgDone()
      }, 600)
    },

    toggleMenu() {
      this.navOpen = !this.navOpen
      this.modalOpen = !this.modalOpen
      if (import.meta.client) {
        document.body.style.overflow = this.navOpen ? 'hidden' : 'visible'
      }
    },

    setHomepage(data: Record<string, any>) {
      this.homePage = data
    },

    setWindowWidth(size: number) {
      this.windowWidth = size
    },

    setConnection(type: string | null) {
      this.connection = type
    }
  }
})
