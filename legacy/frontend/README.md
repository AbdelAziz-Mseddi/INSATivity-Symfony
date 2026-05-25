Legacy frontend files moved here to keep the Symfony project root clean.

What was moved:
- `pages/` → `legacy/frontend/pages/` (static HTML files)
- `scripts/` (JS) → `legacy/frontend/scripts/` (including `club-dashboard/`)
- `styles/` (CSS) → `legacy/frontend/styles/`

What was not moved:
- `assets/` (images, icons, uploads) was left at the repository root under `/assets/` to avoid copying large binary files.

Notes for the team:
- You can serve these static pages directly from `legacy/frontend/pages/` during migration, or move them into `public/` when ready.
- Update asset paths if you relocate `assets/` later.
- To restore the original layout, the moved files keep the same relative paths internally.

Suggested next steps:
1. Option A (quick): Create symlinks from `public/` to `legacy/frontend/*` for local testing.
2. Option B (standardize): Move `assets/` into `legacy/frontend/assets/` too (requires moving binaries).
3. Option C (migrate): Convert these HTML pages to Twig templates in `templates/` and wire controllers.

If you want, I can either:
- Move `assets/` as well (I will only move text/binary-safe files; large binaries will be copied), or
- Create `public/` symlinks and small nginx/apache examples to serve legacy pages.
