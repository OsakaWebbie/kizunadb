# Per-client theming (`css/custom/<client>/`)

KizunaDB's default look is defined by **`css/style.css`**, which uses CSS custom
properties (variables) declared in its `:root` block. To re-theme a client, you only
override the variables you want — you do **not** copy the whole stylesheet.

Client theme assets live here, in the webroot, named by subdomain:

```
public/css/custom/<client>/
    colors.css            ← REQUIRED for a customized client: a :root {} override block
    favicon.ico           ← OPTIONAL: per-client browser-tab icon (else the KizunaDB default)
```

If a `favicon.ico` is present in a client's folder, `css_bundle()` links it (cache-busted) for
that subdomain instead of the site default `public/favicon.ico` — handy for telling multiple open
KizunaDB tabs apart (e.g. the same logo in a different color or with a border).

`<client>` is the subdomain (e.g. `acme.kizunadb.com` → `css/custom/acme/`).
**A client on the default theme needs no folder here at all.**

`css_bundle()` (in `functions.php`) automatically links a client's `colors.css` after
`style.css`, so its `:root` values win.

---

## Creating a `colors.css` (the common case)

Most clients only need to change the **brand** colors; everything else
(navigation, sections, table headers, links, jQuery UI chrome…) derives from them.

```css
/* public/css/custom/<client>/colors.css — overrides KizunaDB defaults */
:root {
  --primary-light:    #cfe0c0;   /* light accent (table headers, highlights, default buttons) */
  --primary-medium:   #6a8a4f;   /* medium accent (h2, titles, active states) */
  --primary-dark:     #3a5028;   /* dark accent (button text) */
  --secondary-light:  #e8c39a;   /* light logo-tone (datepicker "today") */
  --secondary-medium: #b5651d;   /* logo-tone accent (h1, person-info) */
  --secondary-dark:   #7a3e10;   /* nav hover, section/legend borders, sorted columns, UI header */
  --dark-header:      #4a2a08;   /* nav bar background */
}
```

You can override **any** variable, not just the five brand colors. For example a client
that uses no blue could also re-point the link colors (which default to blue and are
*not* derived from the palette):

```css
:root {
  /* …brand colors… */
  --link:       #b5651d;
  --link-hover: #7a3e10;
}
```

### All overridable variables

Override any of these in a client `colors.css`. The brand palette is what you usually
change; everything else derives from it. For the actual default values (and how each
derives), see the `:root` block in `css/style.css` — that's the single source of truth.

**Brand palette** (the ones you usually change):

| Variable | Drives |
|---|---|
| `--primary-light` | table headers/cell borders, highlight, nav link, default buttons |
| `--primary-medium` | h2, active states, UI borders/text, inner borders |
| `--primary-dark` | button text |
| `--secondary-light` | datepicker "today" |
| `--secondary-medium` | h1, page title, person-info |
| `--secondary-dark` | nav hover, section/legend/fieldset borders, sorted columns, UI header |
| `--dark-header` | nav bar background |

**Everything else** (derived/semantic — override individually if you need to):

```
Layout:        --body-bg --main-bg --main-border
Navigation:    --nav-bg --nav-link --nav-bg-hover --nav-link-hover --scrollnav-bg
Page chrome:   --title
Sections:      --section-border --section-title-border --section-title-bg --section-title
               --fieldset-border --legend-bg --legend
Typography:    --h1 --h2 --h3 --link --link-hover --link-more --alert --validation --highlight
Forms/tables:  --input-bg --input-border --inline-label
               --table-header-bg --table-header-border --table-cell-border
Special:       --del-confirm --photo-border --person-info-title --person-info-border
               --leader-bg --inner-border --active-event-bg --inactive-event-bg
jQuery UI:     --ui-header-bg --ui-header-text
               --ui-default-bg --ui-default-border --ui-default-text
               --ui-active-bg --ui-active-text
               --ui-hover-bg --ui-hover-border --ui-hover-text
```

### Lightening or darkening a palette color

To derive a shade of an existing color (e.g. a hover state from a resting one),
use CSS `color-mix()` rather than hardcoding a second hex value:

```css
color-mix(in srgb, var(--primary-medium) 75%, White)   /* 75% color + 25% White → lighter  */
color-mix(in srgb, var(--primary-medium) 80%, Black)   /* 80% color + 20% Black → darker   */
```

The percentage is the knob: lower = a bigger shift toward White/Black. This is how the
default hover seams derive their shade from the resting widget colors, so the effect
tracks whatever palette a client sets.

---

## jQuery UI widgets

There are no per-client jQuery UI themes — one mechanism serves everyone. A single
**grayscale** base theme (`css/jquery-ui.min.css`) ships neutral widgets (gray icons,
light-gray panels; error states stay red), and `css/style.css` — loaded *after* the base
so it wins the cascade — recolors the widget *chrome* (header bars, buttons, hover/active
states, borders) to the palette via the `--ui-*` variables above, with a translucent
CSS-gradient "sheen" for a subtle raised look. This applies to every client; a `colors.css`
just changes the palette those `--ui-*` variables resolve to.

Why grayscale base + CSS instead of a per-client ThemeRoller theme: ThemeRoller's textured
themes are baked, per-color PNGs, and its image generator no longer produces them — so custom
textured themes aren't possible. A neutral base plus a CSS recolor gives every palette
consistent, texture-free widgets with no per-client files.
