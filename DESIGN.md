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

Each has a `-050` tint for backgrounds. Never use a saturated semantic colour
as a large background fill.

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

| Variant | Look | Use |
|---|---|---|
| primary | `--vv-primary-700` fill, white text | One per view |
| accent | `--vv-accent-500` fill, white text | Homepage hero CTA only |
| secondary | white fill, `--vv-ink-300` border, `--vv-ink-700` text | Everything else |
| ghost | no fill, no border, primary text | Tertiary actions |
| danger | `danger` text on `danger-050` | Destructive |

Heights: 40px default, 48px large, 32px small. Radius `md`. Icon and label
separated by 8px. Never a button with only an icon unless it has an
`aria-label` and a tooltip.

### Cards

Every card follows the same anatomy top to bottom:

```
media (optional, full-bleed to card edges)
padding 24/32
  eyebrow    small, --vv-ink-500, uppercase-ish (Latin only)
  title      h3, --vv-ink-900, 2 lines max, ellipsis
  excerpt    body, --vv-ink-700, 3 lines max, ellipsis
  meta row   small, --vv-ink-500, icon + text pairs
  footer     badges left, action right
```

- The whole card is one link target; nested interactive elements need
  `stopPropagation`.
- Never more than **four** meta items on a card. Pick the four that help a
  buyer decide: category, province, verified status, and one differentiator.
- Fixed height per row in a grid — ragged card bottoms look broken.

### Badges

Height 24px, radius `full`, `label` type, `-050` background with the matching
`-700` text. Verified badges use the accent. Everything else uses neutral or
semantic tints.

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
- Tokens live as CSS custom properties in one file. Components reference the
  variables, never raw hex values.
- Prefer Metronic's existing utility classes over new CSS. Add custom CSS only
  when no utility exists, and put it next to the component.
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
