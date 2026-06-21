# UI Upgrade Pass — Pantherverse

## Plan summary
- Keep the existing purple/gold design system already in `includes/header.php`.
- Improve remaining pages by using the existing components (`page-wrap`, `main-grid`, `card`, `widget`, `form-group`, `alert`, `btn-*`, `q-card`, `q-list`, etc.).
- Reduce inline `style="..."` where safe, without changing behavior.
- Ensure responsiveness and consistent spacing.

## Steps
- [ ] Repo scan for pages that still use inline-heavy layouts (e.g., `style="` scattered across page templates).
- [ ] Create a shortlist of target pages for upgrade (start with `forum.php`, `questions.php`, `profile.php`, `settings/theme.php`, `settings/index.php`, `index.php`).
- [x] Update `forum.php` markup to remove invalid attributes (there is at least one duplicate `style` attribute in the “New Post Form” card) and align to `.card/.form-group/.alert/.pagination` patterns.

- [x] Update `forum.php` markup: fix duplicate/invalid attributes and align to shared design system classes.
- [ ] Update `questions.php` markup: replace remaining inline styles for alerts/sidebar rows; unify vote/like/view boxes with the same `.q-stat-box` patterns.


- [ ] Update `profile.php` markup: convert inline “profile header / stats row / badges / liked content” blocks to use existing `.card/.q-card/.widget/.badge-pill` when possible.
- [ ] Update `settings/theme.php` and `settings/index.php`: replace inline headings/containers with `.card`, `.card-head`, `.card-title`, `.form-group`, `.btn-gold`.
- [ ] Regression check: run a quick PHP syntax lint across edited files.
- [ ] Manual sanity check checklist: navbar renders, vote/like/bookmark scripts still work, mobile layout doesn’t break.

