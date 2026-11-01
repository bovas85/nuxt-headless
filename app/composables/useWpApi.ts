export const useWpApi = () => {
  const config = useRuntimeConfig()
  const wpDomain = config.public.wpDomain as string
  const clientUrl = config.public.clientUrl as string

  const fetchHomePage = async (pageId = '2') => {
    const url = `${wpDomain}/wp/v2/pages/${pageId}`
    console.info(`[useWpApi] Fetching homepage from ${url} (server: ${import.meta.server})`)
    try {
      return await $fetch<Record<string, any>>(url)
    } catch (error) {
      console.warn(`[useWpApi] Could not fetch homepage from ${wpDomain}/wp/v2/pages/${pageId}:`, error)
      // Provide graceful fallback structure so UI can render even if WP backend is unreachable
      return {
        id: 2,
        title: { rendered: 'Nuxt 4 Headless' },
        acf: {
          hero: {
            title: 'Nuxt 4 Headless',
            description: 'Modern, high-performance Nuxt 4 boilerplate with WordPress REST API.',
            desktop_bg: {
              url: '/icon.png',
              alt: 'Hero Desktop',
              sizes: {
                thumbnail: '/icon.png',
                small: '/icon.png',
                medium: '/icon.png',
                large: '/icon.png',
                ultra: '/icon.png',
                '4k': '/icon.png'
              }
            },
            mobile_bg: {
              url: '/icon.png',
              alt: 'Hero Mobile',
              sizes: {
                thumbnail: '/icon.png',
                small: '/icon.png',
                medium: '/icon.png',
                large: '/icon.png',
                ultra: '/icon.png',
                '4k': '/icon.png'
              }
            }
          }
        }
      }
    }
  }

  const submitContactForm = async (formId: string, formData: FormData) => {
    return await $fetch(`${wpDomain}/contact-form-7/v1/contact-forms/${formId}/feedback`, {
      method: 'POST',
      body: formData
    })
  }

  const submitNewsletter = async (endpoint: string, formData: FormData) => {
    return await $fetch(`${clientUrl}${endpoint}`, {
      method: 'POST',
      body: formData
    })
  }

  return {
    fetchHomePage,
    submitContactForm,
    submitNewsletter
  }
}
