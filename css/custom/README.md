# Per-client theming (`css/custom/<client>/`)

KizunaDB's default look is defined by **`css/style.css`**, which uses CSS custom
properties (variables) declared in its `:root` block. To re-theme a client, you only
override the variables you want — you do **not** copy the whole stylesheet.

Client theme assets live here, in the webroot, named by subdomain:

```
public/css/custom/<client>/
    colors.css            ← REQUIRED for a customized client: a :root {} override block
    favicon.ico           ← OPTIONAL: per-client browser-tab icon (else the KizunaDB default)
    jquery-ui-theme.css   ← OPTIONAL: a full ThemeRoller theme for this client
    images/               ← OPTIONAL: the PNGs that ThemeRoller theme references
```

If a `favicon.ico` is present in a client's folder, `css_bundle()` links it (cache-busted) for
that subdomain instead of the site default `public/favicon.ico` — handy for telling multiple open
KizunaDB tabs apart (e.g. the same logo in a different color or with a border).

`<client>` is the subdomain (e.g. `acme.kizunadb.com` → `css/custom/acme/`).
**A client on the default brown theme needs no folder here at all.**

`header()`/`pageheader()` (in `functions.php`, via `css_bundle()`) automatically links
a client's `colors.css` after `style.css`, so its `:root` values win.

---

## Creating a `colors.css` (the common case)

Most clients only need to change the five **brand** colors; everything else
(navigation, sections, table headers, links, jQuery UI chrome…) derives from them.

```css
/* public/css/custom/<client>/colors.css — overrides KizunaDB defaults */
:root {
  --primary-light:    #cfe0c0;   /* light accent (table headers, highlights, default buttons) */
  --primary-medium:   #6a8a4f;   /* medium accent (h2, titles, active states) */
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

### All overridable variables (defaults shown)

```
Brand palette:
  --primary-light: LightSteelBlue   --primary-medium: SteelBlue
  --secondary-medium: #CC9944       --secondary-dark: #844E0C   --dark-header: #583907
Layout:        --body-bg --main-bg --main-border
Navigation:    --nav-bg --nav-link --nav-bg-hover --nav-link-hover --scrollnav-bg
Page chrome:   --title --title-bg
Sections:      --section-border --section-title-border --section-title-bg --section-title
               --fieldset-border --legend-bg --legend
Typography:    --h1 --h2 --h3 --link --link-hover --link-more --alert --validation --highlight
Forms/tables:  --input-bg --input-border --inline-label --table-header-bg
Special:       --del-confirm --photo-border --person-info-title --person-info-border
               --leader-bg --inner-border --active-event-bg --inactive-event-bg
jQuery UI:     --ui-header-bg --ui-header-text --ui-default-bg --ui-default-text
               --ui-active-bg --ui-active-text --ui-hover-bg --ui-hover-text
```

(See the `:root` block in `css/style.css` for how each derives from the palette.)

---

## Converting an old `colors.php` → `colors.css`

The old per-client `client/<client>/css/colors.php` set PHP variables. Convert each
`$varName = "value";` to a CSS line `--var-name: value;` — i.e. drop the `$`, insert a
hyphen at each word boundary (kebab-case), and put it inside `:root { … }`.

| old `colors.php` | new `colors.css` |
|---|---|
| `$primarylight`   | `--primary-light` |
| `$primarymedium`  | `--primary-medium` |
| `$secondarymedium`| `--secondary-medium` |
| `$secondarydark`  | `--secondary-dark` |
| `$darkheader`     | `--dark-header` |
| `$navbghover`     | `--nav-bg-hover` |
| `$navlinkhover`   | `--nav-link-hover` |
| `$sectiontitlebg` | `--section-title-bg` |
| `$personinfotitle`| `--person-info-title` |
| `$inactiveeventbg`| `--inactive-event-bg` |
| …(same pattern for the rest)… | |

Old values that referenced another PHP variable (e.g. `$navbg = $darkheader;`) become a
`var()` reference: `--nav-bg: var(--dark-header);` — but if the client only customized the
five brand colors, you can usually omit the derived ones entirely and let `style.css`'s
defaults do the work.

Empty old values (e.g. `$bodybg = "";`) were placeholders that fell back to a default —
just omit them.

---

## jQuery UI: three tiers

1. **No `colors.css`** → default textured brown/blue ThemeRoller theme (unchanged).
2. **`colors.css` only** → `css/jquery-ui-vars.css` automatically re-tints the widget
   *chrome* (header bars, default/hover/active buttons) toward the client palette using
   **flat** colors. Caution/error states and icons keep their defaults. No extra files
   needed. (Flat because ThemeRoller's glass textures are baked PNGs that CSS can't recolor.)
3. **Texture-perfect** → generate a theme at <https://jqueryui.com/themeroller/>, download
   it, and drop the theme CSS here as **`jquery-ui-theme.css`** plus its **`images/`**
   folder. When present, it's linked after the base theme and fully replaces the look
   (textures and all). The `colors.css` still themes everything else on the page.
