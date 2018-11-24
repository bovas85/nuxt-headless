import { cacheAdapterEnhancer } from 'axios-extensions'
import LRUCache from 'lru-cache'
const ONE_HOUR = 1000 * 60 * 60

const defaultCache = new LRUCache({ maxAge: ONE_HOUR })

export default function ({ $axios }) {
  const defaults = $axios.defaults
  // https://github.com/kuitos/axios-extensions
  defaults.adapter = cacheAdapterEnhancer(defaults.adapter, {
    enabledByDefault: false,
    cacheFlag: 'useCache',
    defaultCache
  })
}
