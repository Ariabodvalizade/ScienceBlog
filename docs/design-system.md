# Design System — theme "Meridian"

The visual language is taken from the client's reference, a minimalist editorial blog layout called "blank". It is
translated into an academic journal context. Its principles:

1. **Quiet and typographic.** Content is the interface. Serif display titles and generous white space; decoration
   comes from hairline rules, not boxes or shadows.
2. **Monochrome and one accent.** Black ink on white. A single, restrained academic accent (deep teal by default,
   configurable) is used for links, focus and primary actions.
3. **Structured reading.** A three-column reading layout: context rail | text | tools rail, separated by 1px rules,
   as in the reference.
4. **Scholarly first.** DOIs, ORCID, references and citation tools are first-class UI, never afterthoughts.

---

## 1. Tokens

All tokens are CSS custom properties defined in `plugins/themes/meridian/styles/tokens.less`, so theme options can
override them at runtime.

### Colour

| Token | Value | Use |
|---|---|---|
| `--m-bg` | `#ffffff` | Page background |
| `--m-bg-subtle` | `#f7f7f5` | Subtle panels (abstract box, code, table stripes) |
| `--m-ink` | `#111111` | Headings, wordmark, primary buttons |
| `--m-text` | `#222222` | Body text |
| `--m-muted` | `#6b6b6b` | Meta text, captions (5.3:1 on white — passes AA) |
| `--m-rule` | `#e8e8e8` | Hairlines, column dividers, borders |
| `--m-rule-strong` | `#cfcfcf` | Input borders, blockquote rule (with ink) |
| `--m-accent` | `#0f5c63` | Links, focus ring, active states (7.4:1 on white) — **theme option** |
| `--m-accent-ink` | `#0a4348` | Link hover |
| `--m-accent-tint` | `#eef5f5` | Highlighted reference, popover header, selected chips |
| `--m-success` / `--m-warning` / `--m-danger` | `#1d6b3a` / `#8a5a00` / `#a42323` | Notifications |
| ORCID | `#a6ce39` | ORCID iD icon only (brand requirement) |

### Typography

Fonts are self-hosted woff2, latin + latin-ext subsets, SIL Open Font License.

| Role | Family | Weights |
|---|---|---|
| Display (titles, headings, wordmark) | **Playfair Display** | 400, 400 italic, 600 |
| Body (reading text, abstracts, references) | **Source Serif 4** | 400, 400 italic, 600 |
| UI (nav, meta labels, buttons, forms) | **Source Sans 3** | 400, 600 |

| Token | Size / line-height | Example |
|---|---|---|
| `--m-fs-hero` | clamp(2rem, 4.5vw, 3.25rem) / 1.15 | Article title hero, page titles (Playfair 400) |
| `--m-fs-h2` | 1.625rem / 1.25 | Section headings |
| `--m-fs-h3` | 1.25rem / 1.3 | Sub-headings, card titles |
| `--m-fs-body` | 1.0625rem (17px) / 1.7 | Reading text (Source Serif 4) |
| `--m-fs-small` | 0.9375rem / 1.55 | References, sidebar lists |
| `--m-fs-meta` | 0.6875rem (11px) / 1.4, **uppercase**, letter-spacing .08em, weight 600 | "RESEARCH ARTICLE · PUBLISHED JUNE 11, 2026 · DOI …" |

Reading measure: 62–72 characters (center column max 700px).

### Spacing, radius, motion

- Spacing scale (rem): `0.25, 0.5, 0.75, 1, 1.5, 2, 3, 4, 6` → `--m-s1 … --m-s9`
- Radius: `0` everywhere (editorial look). The only exceptions are `999px` for avatars and ORCID badges and `2px` for inputs.
- Shadows: none; only the popover and drawer use `0 8px 24px rgba(0,0,0,.08)`.
- Motion: 150–200 ms ease-out; disabled under `prefers-reduced-motion`.

### Grid and breakpoints

- Container: `max-width: 1140px; padding-inline: 24px` (16px on mobile).
- Breakpoints: `mobile ≤ 640px`, `tablet 641–1024px`, `desktop ≥ 1025px`.
- Article grid (desktop): `220px | minmax(0, 700px) | 240px`, with a 48px gap and 1px `--m-rule` vertical dividers.
  - Tablet: `minmax(0,1fr) | 240px`. The left rail moves into a "Contents" disclosure above the text.
  - Mobile: a single column. The tools rail stacks after the text; the PDF button is also sticky at the bottom.

## 2. Components

| Component | Spec |
|---|---|
| **Header** | 64px tall with a 1px bottom rule. Hamburger (left) opens the **drawer**. Centered wordmark (Playfair 600, 1.5rem) or uploaded logo (max-height 40px). Right: search icon (opens a full-width search overlay) and user icon (login/dashboard menu). |
| **Drawer nav** | Slides in from the left, 320px wide (100% on mobile). Shows the primary menu with expandable groups and the user menu at the bottom. Focus is trapped, Esc closes it, and it has `aria-expanded`. |
| **Page hero** | Centered, generous padding (96px top, 64px bottom on desktop). Meta line above or below the title in `--m-fs-meta`. |
| **Meta line** | Uppercase small label; items separated by ` · `; links in ink with an underline on hover. |
| **Buttons** | Primary: ink background, white text, uppercase meta type, 12px × 20px padding. Secondary: 1px ink outline. Link-button: accent text. Hover/focus: accent. |
| **Links (body)** | Accent colour, 1px underline offset 3px, `text-decoration-thickness: 1px`; hover uses `--m-accent-ink`. |
| **Blockquote** | 2px ink left rule, 24px indent, muted italic text (exactly as in the reference). |
| **Keyword chip** | 1px rule border, meta type, no radius; hover uses an accent border. It links to search. |
| **Article list item** | Type label (meta) → title (Playfair 1.25rem) → authors (UI 0.9375rem, muted) → meta line (pages · DOI) → actions (PDF · Abstract ▾ · Cite). 24px vertical padding and a hairline divider. |
| **Issue card** | Cover image (3:4) with the label *Vol 1 · No 2 · 2026* below it and the title. |
| **Related-article card** | Image (or a generated pattern tile if there's no image) with a white label box overlapping the bottom edge; uppercase title — mirrors "Related posts" in the reference. |
| **Author box** | Circular 80px avatar (initials fallback), name (Playfair), affiliation, ORCID badge, bio (Source Serif 0.9375rem). Top and bottom hairlines. |
| **Rail block** | Block title in Playfair 1rem; list items 0.9375rem; category labels in meta type — mirrors "Recent posts" / "Archive" in the reference. |
| **Reference list** | Numbered (`1.`), hanging indent. Each item has a `#ref-N` anchor and inline links: DOI → doi.org, **Crossref**, **Google Scholar** (meta-style small links). The `:target` state gets an accent tint. |
| **Citation popover** | Opens on hover or focus of an in-text citation. Max width 360px; shows the reference text and links; closes on Esc or blur; `role="tooltip"`. |
| **How to Cite** | Bordered panel with a format selector (APA / Vancouver / Harvard …) and a Copy button, plus RIS/BibTeX downloads. |
| **Forms** | 44px inputs, 1px `--m-rule-strong` border, 2px radius, accent focus ring (2px, 2px offset), labels in UI 600. |
| **Notifications** | Left 3px colour rule, subtle background, no radius. |
| **Footer** | Top rule. 3 columns: journal info (name, ISSN, publisher), quick links, and indexing/licence/OAI/RSS. Bottom line: © + CC BY badge. |

Icons: inline SVG sprite (`templates/frontend/components/icons.tpl`), 1.5px stroke, 20px. No icon font.

## 3. Page blueprints

### Home
1. **Hero:** journal name (display), tagline/short description, ISSN line (meta), buttons *Submit Manuscript* (primary) and *Author Guidelines* (secondary).
2. **Current issue:** cover card (left) + 3–5 highlighted articles (list items) + "View full issue →".
3. **Latest articles:** 6 most recent across issues (list, 2 columns on desktop).
4. **Aims & Scope teaser** (from the journal summary) + "About the journal →".
5. **Announcements** (the latest 3, if any).
6. **Indexing / member strip:** logos (grayscale; colour on hover) — theme option.

### Issue (Current / TOC) — ScienceDirect-inspired
- A hero with *Volume X, Issue Y · Month YYYY*, the issue title and description, and the cover on the right (desktop).
- Toolbar: *Download full issue* (if there's an issue galley) · number of articles.
- Sections as H2 with a hairline; article list items with an inline **Abstract ▾** toggle (disclosure button).

### Archives
- A hero titled "Archives". Year headings (Playfair) → a grid of issue cards (4 / 3 / 2 columns). Pagination.

### Article (mirrors the reference's single-post page)
- **Hero:** meta line (SECTION · PUBLISHED DATE), title, subtitle, then an authors line with ORCID badges and
  superscript affiliation markers. The DOI line sits below the authors.
- **Left rail (220px):** *Article info*: received, revised, accepted and published dates, plus the issue link with a
  small cover. *On this page* (sticky): Abstract, Keywords, full-text headings, References, How to cite. *Share*:
  icon links (email, LinkedIn, X, Facebook) — the reference's "Let's connect".
- **Center:**
  - affiliations list
  - **Abstract**, in a subtle panel with structured headings
  - **Keywords** as chips
  - **Full text**, inline when there's an HTML galley
  - **References**
  - **How to Cite**
  - **License** (CC BY badge + text)
  - **Author box(es)**
  - **Related articles** (2 cards, as in the reference)
- **Right rail (240px):** a **Download PDF** primary button (+ other galleys) · DOI (copy button) · stats (views and downloads, if enabled) · *More from this issue* (a list in the "Recent posts" style) · *Archive* (volume list, as in the reference).

### About / static pages
- A page hero, a single prose column (700px) with a sticky in-page TOC on the left on desktop, and prose styles for
  tables, lists and blockquotes.

### Editorial Team
- The OJS 3.5 masthead, grouped by role. Each person is a compact card: name, affiliation, ORCID, and country.

### Search, Login, Register, Announcements, Errors
- The same hero and single-column form layout; forms use the form component; search results use the article list item.

## 4. Accessibility rules

- Colour contrast AA minimum; the accent option is validated (rejected if contrast against white is < 4.5:1).
- All interactive icons have an accessible name; decorative SVGs are `aria-hidden`.
- The drawer, search overlay, abstract toggles and citation popovers are fully keyboard-operable with a visible focus.
- Skip links are kept from OJS; landmarks: `header`, `nav`, `main`, `aside`, `footer`.
- `prefers-reduced-motion` and `prefers-color-scheme` are respected. Dark mode is **not** in scope (it is P2).
