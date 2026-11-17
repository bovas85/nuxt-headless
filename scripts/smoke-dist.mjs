import fs from 'node:fs'
import http from 'node:http'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const repoRoot = path.resolve(__dirname, '..')

const MIME_TYPES = {
  '.css': 'text/css; charset=utf-8',
  '.html': 'text/html; charset=utf-8',
  '.ico': 'image/x-icon',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.mjs': 'application/javascript; charset=utf-8',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.txt': 'text/plain; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8'
}

const FATAL_ERROR_MARKERS = [
  '<div class="page-not-found"',
  '__nuxt_error',
  'data-nuxt-error',
  'NuxtError',
  '500 Internal Server Error',
  '404 Page Not Found'
]

function resolveOutputDir() {
  if (process.env.SMOKE_DIST_DIR) {
    return path.resolve(repoRoot, process.env.SMOKE_DIST_DIR)
  }

  const distDir = path.join(repoRoot, 'dist')
  const nitroPublicDir = path.join(repoRoot, '.output', 'public')
  const isNetlify = process.env.NETLIFY === 'true' || process.env.NITRO_PRESET === 'netlify-static'

  if (isNetlify && fs.existsSync(path.join(distDir, 'index.html'))) {
    return distDir
  }

  const candidates = [nitroPublicDir, distDir]
    .filter(dir => fs.existsSync(path.join(dir, 'index.html')))
    .map(dir => ({
      dir,
      mtimeMs: fs.statSync(path.join(dir, 'index.html')).mtimeMs
    }))
    .sort((a, b) => b.mtimeMs - a.mtimeMs)

  if (candidates.length === 0) {
    throw new Error('No generated static output found (checked .output/public and dist). Run "yarn generate" first.')
  }

  return candidates[0].dir
}

function resolveFilePath(outputDir, requestUrl) {
  const { pathname } = new URL(requestUrl || '/', 'http://127.0.0.1')
  const decoded = decodeURIComponent(pathname)
  const cleanRel = decoded.replace(/^\/+/, '')
  const candidate = path.resolve(outputDir, cleanRel)

  if (candidate !== outputDir && !candidate.startsWith(outputDir + path.sep)) {
    return null
  }

  if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) {
    return candidate
  }

  const indexCandidate = path.join(candidate, 'index.html')
  if (fs.existsSync(indexCandidate) && fs.statSync(indexCandidate).isFile()) {
    return indexCandidate
  }

  return null
}

function assertRouteHtml(route, html) {
  if (!html || html.trim().length === 0) {
    throw new Error(`Route ${route} returned empty HTML`)
  }

  const titleMatch = html.match(/<title>([^<]+)<\/title>/i)
  if (!titleMatch || !titleMatch[1] || titleMatch[1].trim().length === 0) {
    throw new Error(`Route ${route} is missing a non-empty <title>`)
  }

  if (/page not found/i.test(titleMatch[1])) {
    throw new Error(`Route ${route} rendered an error title: ${titleMatch[1]}`)
  }

  if (!html.includes('<main')) {
    throw new Error(`Route ${route} is missing rendered <main> content`)
  }

  for (const marker of FATAL_ERROR_MARKERS) {
    if (html.includes(marker)) {
      throw new Error(`Route ${route} contains fatal error marker: ${marker}`)
    }
  }

  if (route.startsWith('/privacy-policy') && !html.includes('Privacy Policy')) {
    throw new Error(`Route ${route} did not contain expected "Privacy Policy" heading`)
  }

  return titleMatch[1].trim()
}

async function main() {
  const outputDir = resolveOutputDir()
  const relDir = path.relative(repoRoot, outputDir) || '.'

  const server = http.createServer((req, res) => {
    const filePath = resolveFilePath(outputDir, req.url)
    if (!filePath) {
      res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' })
      res.end('Not Found')
      return
    }

    const ext = path.extname(filePath).toLowerCase()
    const contentType = MIME_TYPES[ext] || 'application/octet-stream'
    res.writeHead(200, { 'Content-Type': contentType })
    fs.createReadStream(filePath).pipe(res)
  })

  await new Promise((resolve, reject) => {
    server.once('error', reject)
    server.listen(0, '127.0.0.1', resolve)
  })

  try {
    const address = server.address()
    if (!address || typeof address === 'string') {
      throw new Error('Failed to bind ephemeral HTTP server address')
    }

    const baseUrl = `http://127.0.0.1:${address.port}`
    const routes = ['/', '/privacy-policy/']

    for (const route of routes) {
      const res = await fetch(`${baseUrl}${route}`)
      if (res.status !== 200) {
        throw new Error(`Expected HTTP 200 for ${route}, got ${res.status}`)
      }

      const contentType = res.headers.get('content-type') || ''
      if (!contentType.includes('text/html')) {
        throw new Error(`Expected text/html content-type for ${route}, got ${contentType}`)
      }

      const html = await res.text()
      const title = assertRouteHtml(route, html)
      console.info(`[smoke-dist] OK ${route} (${relDir}) -> 200, title="${title}", ${html.length} bytes`)
    }
  } finally {
    server.closeAllConnections?.()
    await new Promise(resolve => server.close(resolve))
  }
}

main().catch((error) => {
  console.error('[smoke-dist] FAILED:', error)
  process.exitCode = 1
})
