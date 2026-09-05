# Viravach — Design Language

This file is the single source of truth for visual decisions in this project.
Every UI prompt must start by reading it. When this file and an instruction
disagree, this file wins unless the instruction explicitly overrides it.

---

## 1. What we are building

A multilingual B2B export directory. The primary visitor is an **international
buyer** evaluating whether an Iranian supplier is credible. The secondary
visitor is an Iranian business owner deciding whether to list.

Every design decision serves one of two goals:

1. **Credibility.** The buyer has never heard of these companies. The interface
   must feel like a serious commercial platform, not a hobby project.
2. **Scannability.** The buyer compares many suppliers quickly. Density of
   *information*, not density of *pixels*.

### Reference points

| Site | What we take from it |
|---|---|
| airbnb.com | Generous whitespace, soft card elevation, photo-forward listings, restrained colour |
| capterra.com | Directory structure, filter patterns, trust signals, comparison affordances |
| designrush.com | Category grids, listing card information hierarchy |

### What we are NOT

Not a marketplace with prices and carts. Not a startup landing page with
gradients and glow effects. Not a traditional Iranian corporate site with
crowded blue gradients and stock photos of handshakes.

---

## 2. Colour

Metronic's default primary (`#009EF7`) is **not** our brand colour. Override it.

### Token architecture — how `--vv-*` actually works

There is **no central token file and no separate dark palette**. The
implemented pattern, and the required pattern for any new component:

- `--vv-*` custom properties are **declared locally on the component's root
  selector** in `resources/css/app.css`, inside its `/* ===== component ===== */`
  block. Today `.vv-card` and `.vv-footer` declare full local sets; other
  components write the literal value inline with a `/* --vv-name */` comment
  marking the token it corresponds to.
- **Dark mode is a remap, not a palette.** Under `[data-bs-theme=dark]` the
  same `--vv-*` names are re-pointed at Metronic/Bootstrap tokens:
  `--bs-body-bg`, `--bs-body-color`, `--bs-secondary-color`,
  `--bs-secondary-bg`, `--bs-border-color`, `--bs-emphasis-color`,
  `--bs-primary-text-emphasis`, `--bs-success-text-emphasis`,
  `--bs-success-bg-subtle`. Components that use literal values instead of
  local variables get explicit `[data-bs-theme=dark]` override rules in
  app.css instead.
- A new component should declare its `--vv-*` variables on its own root class
  (so one dark-mode remap block covers it) and must never invent new hex
  values in dark mode.

### Brand

```
--vv-primary-900: #062A46
--vv-primary-800: #0A3A5E
--vv-primary-700: #0F4C81   ← brand primary
--vv-primary-600: #1A6BA8
--vv-primary-500: #2B87C9
--vv-primary-100: #DCEBF6
--vv-primary-050: #F1F7FB
```

`--vv-primary-700` is the brand blue: serious, commercial, not the generic
SaaS blue everyone uses.

### Accent — use sparingly

```
--vv-accent-600: #B45B26
--vv-accent-500: #D97440   ← warm terracotta
--vv-accent-100: #FBEADF
```

The accent is the only warm colour in the system and the only visual nod to
Iran. It appears on **at most one element per screen**: a primary CTA, a
"verified" badge, or a section marker. Two accent elements competing on one
screen is a bug.

Never use the accent for body text, borders, or backgrounds larger than a badge.

Note: the accent tokens above are the documented reserve — no `--vv-accent-*`
value is currently consumed in app.css. The verified badge, which this section
once assigned to the accent, is implemented in green (see Semantic below and
§7 Badges). If you reach for the accent, it must be a deliberate new decision,
not a default.

### Neutrals

```
--vv-ink-900: #0F1720   ← headings
--vv-ink-700: #33414F   ← body text
--vv-ink-500: #64748B   ← secondary text, labels
--vv-ink-300: #CBD5E1   ← borders
--vv-ink-100: #E9EEF3   ← dividers, subtle fills
--vv-ink-050: #F6F8FA   ← page background
--vv-white:   #FFFFFF   ← card background
```

Page background is `--vv-ink-050`, cards are white. This separation is what
creates depth — not shadows.

### Semantic

```
success #157F4E   warning #B45309   danger #B42318   info #0F4C81
```

Each has a `-050` tint for backgrounds. The success tint has one literal
implemented value — `--vv-success-050: #E9F7F0` (declared on `.vv-card`,
paired with `--vv-success-700: #157F4E` for the verified badge text). Never
use a saturated semantic colour as a large background fill.

### Rules

- Body text is `--vv-ink-700` on white. Never pure black, never grey-on-grey.
- Minimum contrast 4.5:1 for text, 3:1 for UI borders. Check it, don't assume.
- No gradients anywhere except, optionally, a single flat-to-flat overlay on a
  hero image for text legibility.
- Colour never carries meaning alone — always pair it with an icon or label.

---

## 3. Typography

### Families

```
Persian / Arabic : Vazirmatn
Latin / Cyrillic : Inter
```

Vazirmatn is used for `fa` and `ar`. Inter for `en`, `ru`, `tr`. Set the family
on `<html>` based on the active locale — never per-component.

Numbers inside Persian text use Persian digits; numbers in technical contexts
(IDs, codes, phone numbers, URLs) stay Latin and LTR.

### Scale

Use these and only these. If a size is not on this list, it does not exist.

| Token | Size / line-height | Weight | Use |
|---|---|---|---|
| display | 44 / 1.15 | 700 | Homepage hero only |
| h1 | 32 / 1.25 | 700 | One per page |
| h2 | 24 / 1.3 | 700 | Section headings |
| h3 | 19 / 1.4 | 600 | Card titles, subsections |
| body-lg | 17 / 1.7 | 400 | Article body, about text |
| body | 15 / 1.65 | 400 | Default |
| small | 13 / 1.5 | 400 | Meta, captions |
| label | 12 / 1.4 | 600 | Uppercase-ish labels, badges |

Persian at a given pixel size reads smaller than Latin. For `fa` and `ar`, add
1px to body and body-lg. Do not scale headings.

### Rules

- Line length caps at **72 characters** for Latin, **60** for Persian. Use a
  max-width on text containers, not on the card.
- One `<h1>` per page. Section headings step down without skipping levels.
- Never centre a paragraph longer than two lines.
- Never use all-caps for Persian or Arabic.

---

## 4. Space

An 8px base grid. Allowed values only:

```
4  8  12  16  24  32  48  64  96
```

| Context | Space |
|---|---|
| Inside a badge / chip | 4–8 |
| Between a label and its field | 8 |
| Between fields in a form | 24 |
| Card inner padding | 24 (mobile) / 32 (desktop) |
| Between cards in a grid | 24 |
| Between sections on a page | 64 (mobile) / 96 (desktop) |

**The most common mistake in this project is not enough space between
sections.** When a page feels cramped, the fix is almost always increasing
section spacing, not shrinking content.

### Containers

```
max-width 1280px, side padding 16 (mobile) / 24 (tablet) / 32 (desktop)
```

Text-heavy blocks sit in a narrower 720px column inside that container.

---

## 5. Shape and elevation

```
--vv-radius-sm: 8px    inputs, badges, small buttons
--vv-radius-md: 12px   cards, buttons
--vv-radius-lg: 16px   media blocks, modals
--vv-radius-full: 999px  avatars, pills
```

Elevation is deliberately shallow. Depth comes from the background/card
contrast, not from shadows.

```
--vv-shadow-sm: 0 1px 2px rgba(15,23,32,.06)
--vv-shadow-md: 0 4px 12px rgba(15,23,32,.08)
--vv-shadow-lg: 0 12px 32px rgba(15,23,32,.10)
```

- Cards at rest: `shadow-sm` + a 1px `--vv-ink-100` border.
- Cards on hover: `shadow-md`, no scale transform, no border colour change.
- `shadow-lg` is for overlays only — modals, dropdowns, drawers.
- No inner shadows. No coloured shadows. No glow.

---

## 6. Imagery

| Use | Ratio | Notes |
|---|---|---|
| Company featured image | 16:9 | `object-fit: cover`, `radius-lg` |
| Listing card thumbnail | 16:9 | Same image, smaller conversion |
| Logo | 1:1 | Contained, never cropped, on white |
| Category / country tile | 3:2 | |

Rules:

- Logos are **contained**, never cropped, never stretched. Put them on a white
  or `--vv-ink-050` plate with padding, never bleeding to the card edge.
- Every image needs a real localized `alt`. Decorative images get `alt=""`.
- The first image above the fold is `loading="eager"` + `fetchpriority="high"`.
  Everything else is `loading="lazy"`.
- Reserve space with `aspect-ratio` so nothing shifts as images load.
- When an image is missing, render a neutral placeholder with the company's
  initial — never a broken frame, never an empty gap.

---

## 7. Components

### Buttons

There is **no `vv-*` button system in app.css**. Buttons use Metronic /
Bootstrap `btn` utility classes directly: `btn btn-primary` for the primary
action, `btn-light` for quiet alternatives, `btn-sm` / `btn-icon` for compact
and icon-only instances.

- `btn-primary` (the Metronic surface, restyled to brand primary) — one per
  view. There is **no implemented accent button variant**; the accent CTA in
  this table is a documented reserve, not shipped CSS.
- Quiet / secondary: `btn-light`, `btn-outline` — everything else.
- Destructive: `btn-light-danger`.
- Icon-only buttons carry an `aria-label` and a tooltip.

One custom button-adjacent pattern exists: `.vv-action` on company cards — a
`--vv-primary-700` coloured, `font-weight: 600`, 13px inline-flex text link
with a chevron; the chevron mirrors in RTL via `[dir="rtl"] .vv-chevron`.

### Cards

Implemented as `company-elements/⚡company-card`, classes below. Every card
follows the same anatomy top to bottom:

```
.vv-media        optional media block, full-bleed to card edges
  .vv-media-placeholder + .vv-media-initial   when no image: ink-050 plate
  .vv-logo-plate   44x44 white plate, radius-sm, inset-inline-start/bottom 12px
.vv-body         padding 20px
  .vv-eyebrow     small, --vv-ink-500, ellipsis; `.vv-eyebrow-uppercase`
                  opt-in (Latin only)
  .vv-title       h3-size (19/1.4, 600), --vv-ink-900, 2 lines max, ellipsis
  .vv-excerpt     body (15/1.65), --vv-ink-700, 3 lines max, ellipsis
  .vv-spacer      flex-grow filler so rows align
  .vv-meta        small (13px), --vv-ink-500, 16px-gap icon + text pairs
  .vv-divider     1px --vv-ink-100 rule (optional)
  .vv-card-footer badges left, .vv-action right
```

- The card root is `.vv-card`: white surface, 1px `--vv-ink-100` border,
  radius-md, `shadow-sm`; on hover `shadow-md` **and** the media image scales
  to 1.04 with `saturate(.85)` (200ms ease-out) — this is implemented
  behaviour, not a violation of "no scale on the card": the card box itself
  never scales, only its image.
- The card footer class is deliberately **`.vv-card-footer`, not
  `.vv-footer`** — `.vv-footer` is the ⚡footer component's dark surface. The
  card footer has no background of its own and inherits the card's white
  surface.
- Missing images render the neutral `.vv-media-placeholder` with the
  company's initial (`.vv-media-initial`) — never a broken frame.
- Media is always `aspect-ratio: 16/9`, `object-fit: cover`.
- The whole card is one link target; nested interactive elements need
  `stopPropagation`.
- Never more than **four** meta items on a card. Pick the four that help a
  buyer decide: category, province, verified status, and one differentiator.
- All card colours are declared as local `--vv-*` variables on `.vv-card`, so
  one `[data-bs-theme=dark]` remap block flips the entire card to Metronic's
  dark surfaces.
- `prefers-reduced-motion` disables the media hover transition.

### Badges

Implemented badge: `.vv-badge-verified` — height ~24px, radius `full`,
`label` type (12px, 600), 4px/8px padding, **`--vv-success-050` background
with `--vv-success-700` text (green)**, 4px icon gap. It is *not* accent —
the accent assignment in earlier revisions of this file was never implemented.
Everything else uses neutral or semantic tints.

### Forms

Label above field, always visible — never placeholder-as-label. Field height
44px, radius `sm`, 1px `--vv-ink-300` border, `--vv-primary-500` on focus with
a 3px `--vv-primary-100` ring. Helper text `small` in `--vv-ink-500` below.
Errors replace helper text in `danger`, with an icon.

Fields holding URLs, emails, phone numbers or codes are `dir="ltr"` even on
RTL pages. The wrapper carries the `dir`, not just the input, so any prefix
addon sits on the visual left.

### Empty states

Every list, grid and table needs one: an icon, a one-line explanation, and
exactly one action. Never an empty container. Never a bare "No results".

Two implemented scales exist in app.css:

- List scale (`.vv-list-empty` in ⚡company-list): centred column, 12px gap,
  64px block padding, 40px icon in `--vv-ink-300`, body-size text in
  `--vv-ink-500`.
- Widget scale (`.vv-dash-empty` / `.vv-dash-widget-empty` in dashboard
  widgets): same pattern at 48px/32px icons; the page-level empty adds an
  h2-size (24/1.3, 700) title in `--vv-ink-900` and a 480px-max body.

### Shared section block (⚡hero, ⚡category-grid, ⚡recent-companies, ⚡state-grid, ⚡key-numbers, ⚡world-globe)

Every homepage section shares the same skeleton:

```
section root       .vv-hero | .vv-section | .vv-recent | .vv-states |
                   .vv-numbers | .vv-globe-section   (padding-block 32px,
                   24px below 991.98px; hero: 48px 32px, 720px max-width)
  heading          h2 — 24/1.3, 700, --vv-ink-900, 4px (24px for numbers)
                   bottom margin
  subheading       .vv-*-sub — 15/1.65, --vv-ink-500, 24px bottom margin
  content          section-specific grid/list below
```

The hero is the one h1 on the homepage: 44/1.15, 700 (32/1.25 below
991.98px), with a 17/1.65 subheading. Text is left-aligned logically
(`text-align: start` under `[dir="rtl"]`). In dark mode each section's h2 and
subheading get explicit `--bs-emphasis-color` / `--bs-secondary-color`
overrides.

### Category tile grid (⚡category-grid)

```
.vv-tile-grid      auto-fill grid, minmax(240px, 1fr), 24px gap
.vv-tile           white card: ink-100 border, radius-md, shadow-sm →
                   shadow-md on hover; 24px padding; flex column, 4px gap
  .vv-cat-icon     40x40 icon, --vv-ink-500 → --vv-primary-700 on tile
                   hover; or .vv-cat-icon-fallback (40px glyph) /
                   contained img for uploaded icons
  .vv-tile-title   h3-size (19/1.4, 600), --vv-ink-900
  .vv-tile-count   small (13px), --vv-ink-500
```

Whole tile is a link; reduced-motion kills the tile and icon transitions;
dark mode remaps surface, border, title, count, and icon colours to
`--bs-*`.

### State links (⚡state-grid)

```
.vv-states-grid    auto-fill grid, minmax(180px, 1fr), 12px gap
.vv-state-link     compact link row: 12px/16px padding, white, ink-100
                   border, radius-sm, 15px text, --vv-primary-700, 600;
                   flex space-between with the count
  .vv-state-count  small (13px), 400, --vv-ink-500, nowrap
.vv-state-link:hover   --vv-primary-050 background, same primary text
```

Dark mode: surface → `--bs-body-bg`, hover surface → `--bs-secondary-bg`,
text → `--bs-primary-text-emphasis`.

### Key-numbers grid (⚡key-numbers)

```
.vv-numbers-grid   4 columns, 24px gap; 2 columns below 767.98px
.vv-number         white card, ink-100 border, radius-md, shadow-sm, 24px
                   padding
  .vv-number-value h1-size (32/1.25, 700), --vv-primary-700
  .vv-number-label small (13px), --vv-ink-500
```

Dark mode: surface/border to `--bs-*`, value to
`--bs-primary-text-emphasis`, label to `--bs-secondary-color`.

### World globe (maps/⚡world-globe)

Country directory section pairing an amCharts orthographic globe with a
country list.

```
.vv-globe-layout   grid: minmax(0, 2fr) | minmax(0, 3fr), 24px gap,
                   stretch; collapses to 1 column below 991.98px
  .vv-globe-list-wrapper + .vv-globe-list
                   the LIST is first in DOM order (SEO surface + no-JS
                   fallback); 8px-gap column, max-block-size 560px scroll
                   (unbounded on mobile)
    .vv-globe-link  link row: 8px/12px padding, white, ink-100 border,
                    radius-sm, 15px --vv-ink-700 text; hover →
                    --vv-primary-050 / --vv-primary-700
      .vv-globe-link-name    600 weight
      .vv-globe-link-count   small, --vv-ink-500, nowrap
  .vv-globe-canvas-wrapper   white media block: radius-lg, shadow-sm,
                    24px padding, overflow hidden
    .vv-globe-canvas          100% width, aspect-ratio 1/1,
                              min-block-size 320px
```

Rules:

- The globe is the supporting visual; it never dominates the layout and never
  precedes the list in the DOM.
- `.vv-globe-canvas-wrapper-empty { display: none }` — when the chart cannot
  initialize, the wrapper collapses instead of leaving a dead empty box.
- The canvas scales with its column (aspect ratio, no fixed pixel width).
- Reduced-motion disables link transitions; dark mode remaps wrapper/link
  surfaces, borders, and text to `--bs-*`.

### Footer (⚡footer)

The page footer is the one deliberately **dark** surface in the system:
`.vv-footer` background `--vv-ink-900`, text `--vv-ink-300`, 15/1.65 body
(16px for `fa`/`ar` per the typography rule; headings are not scaled). It
declares its own local `--vv-*` variables.

```
.vv-footer-grid    4 columns, 32px gap → 2 below 991.98px → 1 below
                   575.98px
  .vv-footer-heading    h3-size (19/1.4, 600) white
  .vv-footer-brand-logo max-height 32px
  .vv-footer-about      ink-300 paragraph
  .vv-footer-links      12px-gap column; hover → white
    .vv-footer-link-count   small ink-500 count after the label
  .vv-footer-social / .vv-footer-social-btn
                        36x36 round buttons, ink-700 fill → primary-700 on
                        hover, white icon
  .vv-footer-contact / .vv-footer-contact-item
                        icon + text rows, 8px gap
.vv-footer-divider / .vv-footer-divider-bottom
                    1px ink-700 rules (32/24px and 24px margins)
.vv-footer-trust + .vv-footer-trust-plate
                    trust-seal plates (eNamad etc.): inline-flex, min
                    96x96, **always white in both themes on purpose** — a
                    dark seal must stay legible on the dark surface; the
                    plate sizes to the badge (min-*, never a fixed cap),
                    image at natural size, never distorted
.vv-footer-locales / .vv-footer-locale
                    language switcher: small ink-500 links, white on hover;
                    `.is-active` is white + 600
.vv-footer-bottom   small (13px) ink-500 legal row, hover white
```

Reduced-motion disables the social-button transition.

### Company list states (⚡company-list)

```
.vv-list-toolbar-label   small ink-500 label above the results
.vv-list-count           small ink-500 result count
.vv-list-empty           empty state (see Empty states)
.vv-list-results         the results container
.vv-list-results-loading loading state
```

Loading behaviour is implemented as an **opacity fade, not a skeleton**: while
a filter change is in flight the results block fades to `.45` with
`pointer-events: none` over 150ms ease-out. It is never removed from the flow,
so the grid keeps its exact height and the page below it (footer included)
never jumps. This intentionally refines the §9 "skeletons, never spinners"
rule for in-place list refreshes.

### Dashboard widgets (dashboard-elements/⚡widget-*)

Dashboard widget chrome is namespaced `.vv-dash-*` and used only by the
dashboard widget components — nothing global.

- `.vv-dash-chart` — 100% × 320px chart host;
  `.vv-dash-chart-empty { display: none }` collapses it when amCharts is
  unavailable rather than leaving a dead 320px frame.
- `.vv-dash-empty` (page-scale) and `.vv-dash-widget-empty` (widget-scale) —
  the two empty-state patterns documented under Empty states; dark mode maps
  their icons to `--bs-border-color`, titles to `--bs-emphasis-color`, and
  bodies to `--bs-secondary-color`.

---

## 8. Right-to-left

The site runs in five locales; `fa` and `ar` are RTL.

- Use **logical** CSS properties everywhere: `margin-inline-start`,
  `padding-inline-end`, `inset-inline-start`, `text-align: start`.
  Never `margin-left` / `padding-right` / `text-align: left`.
- Directional icons (arrows, chevrons) mirror. Non-directional icons
  (search, user, check) do not.
- Numbers, code, URLs and Latin proper nouns stay LTR inside RTL text.
- Test every new component in `fa` **and** `en` before calling it done. A
  layout that only works in one direction is not done.

---

## 9. Motion

- Transitions 150–200ms, `ease-out`. Nothing longer than 300ms.
- Only these properties animate: `opacity`, `transform`, `background-color`,
  `border-color`, `box-shadow`.
- No entrance animations on page load. No parallax. No scroll-triggered
  reveals. They delay content and hurt the metrics we care about.
- Skeletons for loading states, never spinners, except inside buttons.
- Respect `prefers-reduced-motion`.

---

## 10. Implementation

- Stack: Metronic (Bootstrap 5) + Alpine.js + Livewire Volt. KiDuotone icons.
- Reusable UI is a **Volt single-file component**, filename prefixed with `⚡`.
  Invoke with `<livewire:>`, never `<x->`.
- **CSS lives in `resources/css/app.css`, not next to the component.** All
  `vv-*` styles are centralized there in `/* ===== component ===== */` blocks,
  loaded through Vite after Metronic's `style.bundle.css` on the landing
  layout. Per-component `<style>` tags are unreliable because Livewire's SFC
  compiler statically extracts them into an asset this app never serves.
  Tailwind's preflight is deliberately **not** imported (it would override
  Metronic's base look); only theme + utilities layers are.
- Token values follow the architecture in §2: `--vv-*` custom properties
  declared locally on the component's root selector in app.css, remapped to
  Metronic's `--bs-*` tokens under `[data-bs-theme=dark]` for dark mode.
  Components reference the variables (or the literal value annotated with the
  `/* --vv-name */` token comment), never raw hex without the annotation.
- Prefer Metronic's existing utility classes (including `btn` buttons) over
  new CSS. Add custom CSS only when no utility exists, in app.css under the
  component's heading block.
- Every string goes through `__()` with full entries in all five locale files.
- Dates through `LocalizedDate`. Numbers through `number_format()` plus the
  locale-aware currency key — no bespoke formatting helpers.

---

## 11. Checklist before calling any UI work done

- [ ] Renders correctly in `fa` (RTL) and `en` (LTR)
- [ ] Every string is translated in all five locales
- [ ] Only tokens from this file are used — no stray hex, no off-scale sizes
- [ ] Text contrast meets 4.5:1
- [ ] Keyboard reachable, visible focus ring, sensible tab order
- [ ] Empty state exists and is designed
- [ ] Loading state exists and reserves layout space
- [ ] No layout shift as images load
- [ ] Works at 360px width
- [ ] At most one accent element on screen
