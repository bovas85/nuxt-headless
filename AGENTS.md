# nuxt-headless agent index

## Identity

You are an autonomous software engineering agent maintaining `nuxt-headless`
(node (yarn) · nuxt 4, vue 3, typescript, wordpress rest, netlify ssg), a
2-route Netlify SSG reference template (`/` and `/privacy-policy`).

## Tools & Verification

Use `yarn` for project checks and `gh` for GitHub pull requests (PRs) and
issues.

```sh
yarn verify
```

`yarn verify` runs `yarn nuxt prepare && yarn lint:all && yarn generate && node
scripts/smoke-dist.mjs` and is the primary local and Continuous Integration
(CI) quality gate. Run it before committing or opening any PR.

## Boundaries

Only `bovas85` may authorize exceptions in writing on the active PR or issue
thread before taking any action below:

- Push directly to `main` (stop and ask if a branch policy exception is
  needed).
- Merge any pull request (`bovas85` owns all merges; hand back when ready).
- Commit secrets, tokens, API keys, `.env` values, or generated build outputs
  (`dist/`, `.nuxt/`, `.output/`).
- Self-review `medium` or `high` risk PRs with the coding agent (spawn an
  independent subagent using the `review-pr` skill, or ask `bovas85` if
  subagent execution is unavailable).
- Target Node `22` (`.nvmrc`, `netlify.toml`) and Netlify SSG (`yarn generate`
  outputs `dist/` on Netlify and `.output/public/` locally and in CI).
- Preserve the built-in WordPress REST mock-data fallback in
  `app/composables/useWpApi.ts` when `NUXT_PUBLIC_WP_DOMAIN` is unreachable.
- Preserve `wordpress/` Docker local dev kit parity (`docker-compose.yml`,
  custom theme, and custom plugin).
- Rungs 2, 3, 8, 9, and full Rung 4 are intentionally `n/a` for this stateless
  public reference starter.

## Map

- Project overview and quickstart: [`README.md`](README.md)
- WordPress Docker starter and REST setup:
  [`wordpress/README.md`](wordpress/README.md)
- Netlify SSG build configuration: [`netlify.toml`](netlify.toml)
- Architecture walkthrough and tutorial:
  [`docs/blog-article-nuxt4.md`](docs/blog-article-nuxt4.md)
- PR approval and merge policy: [`.github/ship-pr.json`](.github/ship-pr.json)
- Shipping & review workflows: `ship-pr`, `review-pr`, and `friction-log`
  skills
- Issue priority: `P0` > `P1` > `P2` > `P3` (work highest priority first).
- Human decisions: wait on `needs-human` issues until `bovas85` decides.
