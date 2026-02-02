# Frontend UI CSS System - Technical Documentation

## Overview

This document explains how the ArBricks frontend UI CSS system works with the Core Framework CSS variables through a "bridge token" architecture.

---

## Core Framework Variable Analysis

### STEP 1: Variable Extraction

From `/assets/css/default_core_framework_project.css`, the following CSS variable categories were identified:

#### **Colors - Brand**
- `--primary` + variants (`-5` through `-90`, `-d-1` through `-d-4`, `-l-1` through `-l-4`)
- `--secondary` + variants
- `--tertiary` + variants

#### **Colors - Surfaces & Text**
- `--bg-body`, `--bg-surface`
- `--text-body`, `--text-title`
- `--border-primary`
- `--shadow-primary`

#### **Colors - Semantic**
- `--light` + variants (`-5` through`-90`)
- `--dark` + variants (`-5` through `-90`)
- `--success` + variants
- `--error` + variants

#### **Spacing Scale** (fluid via clamp)
- `--space-4xs`, `--space-3xs`, `--space-2xs`
- `--space-xs`, `--space-s`, `--space-m`, `--space-l`
- `--space-xl`, `--space-2xl`, `--space-3xl`, `--space-4xl`

#### **Typography Scale** (fluid via clamp)
- `--text-xs`, `--text-s`, `--text-m`, `--text-l`
- `--text-xl`, `--text-2xl`, `--text-3xl`, `--text-4xl`

#### **Border Radius**
- `--radius-xs`, `--radius-s`, ` --radius-m`, `--radius-l`, `--radius-xl`
- `--radius-full`

#### **Shadows**
-`--shadow-xs`, `--shadow-s`, `--shadow-m`, `--shadow-l`, `--shadow-xl`

#### **Layout**
- `--min-screen-width`, `--max-screen-width`
- `--columns-1` through `--columns-8`
- Component spacing: `--header-space`, `--btn-space`, `--card-space`, `--footer-space`

---

## STEP 2: Bridge Token Mapping

The mapping from Core Framework variables to ArBricks bridge tokens:

| **Plugin Token** | **Core Framework Reference** | **Fallback Value** |
|------------------|------------------------------|-------------------|
| `--arbricks-text` | `var(--text-body, ...)` | `hsl(0, 0%, 20%)` |
| `--arbricks-text-title` | `var(--text-title, ...)` | `hsl(0, 0%, 10%)` |
| `--arbricks-text-muted` | `var(--dark-60, ...)` | `hsl(0, 0%, 50%)` |
| `--arbricks-bg` | `var(--bg-surface, ...)` | `hsl(0, 0%, 100%)` |
| `--arbricks-bg-input` | `var(--dark-5, ...)` | `hsl(0, 0%, 97%)` |
| `--arbricks-bg-hover` | `var(--dark-10, ...)` | `hsl(0, 0%, 94%)` |
| `--arbricks-border` | `var(--border-primary, ...)` | `hsl(0, 0%, 80%)` |
| `--arbricks-border-focus` | `var(--primary, ...)` | `hsl(238, 100%, 62%)` |
| `--arbricks-primary` | `var(--primary, ...)` | `hsl(238, 100%, 62%)` |
| `--arbricks-primary-hover` | `var(--primary-d-1, ...)` | `hsl(240, 56%, 50%)` |
| `--arbricks-primary-light` | `var(--primary-l-3, ...)` | `hsl(254, 100%, 85%)` |
| `--arbricks-space-xs` | `var(--space-xs, ...)` | `clamp(0.5rem, ...)` |
| `--arbricks-space-s` | `var(--space-s, ...)` | `clamp(0.75rem, ...)` |
| `--arbricks-space-m` | `var(--space-m, ...)` | `clamp(1rem, ...)` |
| `--arbricks-space-l` | `var(--space-l, ...)` | `clamp(1.25rem, ...)` |
| `--arbricks-text-s` | `var(--text-s, ...)` | `clamp(0.875rem, ...)` |
| `--arbricks-text-m` | `var(--text-m, ...)` | `clamp(1rem, ...)` |
| `--arbricks-text-l` | `var(--text-l, ...)` | `clamp(1.125rem, ...)` |
| `--arbricks-radius-s` | `var(--radius-s, ...)` | `clamp(0.25rem, ...)` |
| `--arbricks-radius-m` | `var(--radius-m, ...)` | `clamp(0.5rem, ...)` |
| `--arbricks-shadow-s` | `var(--shadow-s, ...)` | `0 1px 3px hsla(...)` |
| `--arbricks-shadow-m` | `var(--shadow-m, ...)` | `0 2px 6px hsla(...)` |

### Rationale

- **Best Candidates**: Chose the most commonly used semantic variables from Core Framework
- **Neutral Fallbacks**: Plugin defaults use neutral colors and fluid spacing to work in any theme
- **No Overrides**: We only REFERENCE core vars, never DEFINE them

---

## STEP 3: Implementation Architecture

### File Structure

```
/assets
  /css
    default_core_framework_project.css  ← REFERENCE ONLY (never loaded)
    frontend-ui.css                     ← Plugin UI CSS (loaded on frontend)
  /js
    (future: minimal JS for copy button)
```

### CSS Architecture

The `frontend-ui.css` file is organized in 8 sections:

1. **Bridge Tokens** - Declared on `.arbricks-ui` wrapper
2. **Base Styles** - Font, reset, box-sizing
3. **Layout Components** - Cards, grids
4. **Form Components** - Labels, textareas, help text
5. **Button Components** - Primary, secondary, ghost variants
6. **Utility Components** - Badges, dividers
7. **Feature-Specific Layouts** - Minifier grid, responsive breakpoints
8. **Accessibility Enhancements** - Focus states, high contrast, reduced motion

### RTL/LTR Support

All spacing and layout use CSS logical properties:

| **Physical Property** | **Logical Property** |
|-----------------------|----------------------|
| `width` | `inline-size` |
| `height` | `block-size` |
| `margin-left/right` | `margin-inline` |
| `margin-top/bottom` | `margin-block` |
| `padding-left/right` | `padding-inline` |
| `padding-top/bottom` | `padding-block` |
| `left/right` | `inset-inline-start/end` |
| `top/bottom` | `inset-block-start/end` |
| `text-align: left` | `text-align: start` |

### Responsive Design

- **Fluid Typography**: Uses `clamp()` for all font sizes
- **Fluid Spacing**: Uses `clamp()` for gaps and padding
- **Container Queries**: Progressive enhancement for narrow containers
- **Breakpoints**: Mobile-first with 30rem and 48rem breakpoints

---

## STEP 4: PHP Render Implementation

### Example Structure

```php
<div class="arbricks-ui arbricks-ui--minifier">
  <div class="arbricks-card">
    <div class="arbricks-minifier-grid">
      
      <!-- Input Field -->
      <div class="arbricks-field">
        <label class="arbricks-label">...</label>
        <textarea class="arbricks-textarea">...</textarea>
        <span class="arbricks-help">...</span>
      </div>
      
      <!-- Output Field -->
      <div class="arbricks-field">...</div>
      
      <!-- Buttons -->
      <div class="arbricks-btn-group">
        <button class="arbricks-btn arbricks-btn--primary">...</button>
        <button class="arbricks-btn arbricks-btn--secondary">...</button>
        <span class="arbricks-badge">...</span>
      </div>
      
    </div>
  </div>
</div>
```

### Security & i18n

- **Escaping**: `esc_html__()`, `esc_attr()`, `esc_html()` used throughout
- **i18n**: All user-facing strings wrapped in `__()` or `esc_html__()`
- **Sanitization**: No user input directly output (JS handles it client-side)
- **Accessibility**: Proper `aria-label`, `aria-describedby`, `aria-live` attributes

---

## How It Works

### Scenario 1: Site WITH Core Framework

If the site already has Core Framework CSS loaded (e.g., in theme):

```css
/* Theme CSS */
:root {
  --primary: hsl(200, 100%, 50%);  /* Blue theme */
  --text-body: hsl(0, 0%, 15%);
  --space-m: 1.5rem;
}
```

Plugin UI automatically inherits these values:

```css
/* Plugin resolves to: */
.arbricks-ui {
  --arbricks-primary: hsl(200, 100%, 50%);  /* ← Inherited! */
  --arbricks-text: hsl(0, 0%, 15%);          /* ← Inherited! */
  --arbricks-space-m: 1.5rem;                /* ← Inherited! */
}
```

**Result**: UI matches the site's design system perfectly!

### Scenario 2: Site WITHOUT Core Framework

If Core Framework is not present:

```css
/* No core variables exist, so plugin uses fallbacks: */
.arbricks-ui {
  --arbricks-primary: hsl(238, 100%, 62%);            /* ← Fallback */
  --arbricks-text: hsl(0, 0%, 20%);                    /* ← Fallback */
  --arbricks-space-m: clamp(1rem, 1.11vw + 0.78rem, 1.75rem); /* ← Fallback */
}
```

**Result**: UI still looks great with modern, neutral defaults!

---

## Critical Rules Compliance

✅ **Rule 1**: Core Framework CSS file is NEVER enqueued  
✅ **Rule 2**: No values copied from reference file  
✅ **Rule 3**: No core variable names declared/assigned  
✅ **Rule 4**: Only referenced inside `var()` with fallbacks  
✅ **Rule 5**: RTL/LTR friendly (logical properties)  
✅ **Rule 6**: Responsive and compact  
✅ **Rule 7**: Fully scoped within `.arbricks-ui`  
✅ **Rule 8**: Accessible focus styles

---

## Usage Examples

### Enqueueing the CSS

```php
// In snippet's apply() method:
public function apply() {
    wp_enqueue_style(
        'arbricks-frontend-ui',
        ARBRICKS_PLUGIN_URL . 'assets/css/frontend-ui.css',
        array(),
        ARBRICKS_VERSION
    );
}
```

### Rendering HTML

```php
// In shortcode callback:
public function render_shortcode( $atts, $content = '' ) {
    ob_start();
    ?>
    <div class="arbricks-ui arbricks-ui--minifier">
        <!-- UI components here -->
    </div>
    <?php
    return ob_get_clean();
}
```

### Adding Custom Feature Layouts

To add a new feature (e.g., QR Generator):

```css
/* In frontend-ui.css */
.arbricks-ui--qr-generator {
    max-inline-size: min(100%, 32rem);
    margin-inline: auto;
}

.arbricks-ui--qr-generator .arbricks-qr-canvas {
    inline-size: 100%;
    block-size: auto;
    aspect-ratio: 1;
}
```

---

## Testing Checklist

- [ ] Test with Core Framework present → UI inherits theme colors
- [ ] Test without Core Framework → UI uses neutral defaults
- [ ] Test in RTL language (Arabic) → Layout mirrors correctly
- [ ] Test on mobile (< 30rem) → Compact mode activates
- [ ] Test on tablet (48rem) → Two-column layout
- [ ] Test with keyboard → All focus states visible
- [ ] Test with screen reader → All labels/descriptions present
- [ ] Test in high contrast mode → Borders remain visible
- [ ] Test with reduced motion → No animations play
- [ ] Test dark mode preference → Colors adapt (if no core theme)

---

## Future Enhancements

- Add more semantic bridge tokens (success, error, warning colors)
- Support Core Framework dark mode classes (`.cf-theme-dark`)
- Add container query polyfill for older browsers
- Create bridge tokens for animation timing
- Add focus-within styles for enhanced keyboard navigation

---

## Summary

The frontend UI CSS system successfully creates a two-mode design:

1. **Integrated Mode**: Inherits Core Framework design tokens for seamless integration
2. **Standalone Mode**: Falls back to modern, neutral defaults

This is achieved through **bridge tokens** declared on `.arbricks-ui` that reference (but never define) Core Framework variables, with literal fallback values.

The system is fully RTL/LTR compatible, responsive, accessible, and requires zero configuration from the user!
