# Notioneers Design System

**Bootstrap 5.3 customized with Notioneers Brand Colors**

📁 **Location:** `/shared/components/design-system/`

---

**Note:** The old design system preview app is now at `/design-system-preview/` (optional showcase app only).
This folder is the actual reusable design system component.

## 📋 Overview

Complete design system combining:
- ✅ **Bootstrap 5.3** - Foundation framework
- ✅ **Notioneers Brand Colors** - Custom SCSS variables
- ✅ **Custom Components** - Tailored UI elements
- ✅ **Typography** - Brand fonts (TWK Lausanne + System fonts)

---

## 🎨 Brand Colors

All colors defined in `scss/_variables.scss`:

```scss
// Primary Brand Colors
$depth: #063312;        // Font color - Primary text, headlines
$halo: #F2F4F2;         // Background color - Surfaces, sections
$bloom: #92EF9A;        // Logo color - Brand accent, CTAs
$shade: #0B1E0F;        // Dark contrast - Text on light surfaces

// Neutral Palette
$root: #454F45;         // Standard text, icons, dark foundations
$sage: #AFCAAF;         // Soft backgrounds, UI strokes
$mist: #DEECDC;         // Light surfaces, large containers
$stone: #7B847B;        // Secondary text, subtle UI elements
$veil: #D1E2D1;         // Input backgrounds, hover states

// Accent Colors
$aeris: #52B4D9;        // Blue accent
$nimbus: #5453CB;       // Purple accent
$muse: #BD73ED;         // Pink accent
$coral: #ED6767;        // Red/Coral accent
$solea: #FFDB76;        // Yellow accent
$cove: #F2AA7F;         // Warm accent
```

See [Brand Guide](https://notioneers.eu/en/ai-style-guide)

---

## 🚀 Quick Start

### 1. Install Dependencies

```bash
cd shared/components/design-system
npm install
```

### 2. Build CSS

```bash
# One-time build
npm run build

# Watch for changes
npm run watch

# Minified build
npm run build:min
```

### 3. Use in Your App

```html
<!-- In your layout/template -->
<link rel="stylesheet" href="../../shared/components/design-system/css/theme.css">
```

---

## 📁 Structure

```
design-system/
├── scss/
│   ├── _variables.scss       # Brand colors & Bootstrap overrides
│   ├── _fonts.scss           # Typography configuration
│   ├── _custom.scss          # Custom components & overrides
│   └── theme.scss            # Main file (imports all)
│
├── css/
│   ├── theme.css             # Compiled CSS (unminified)
│   └── theme.css.map         # Source map for debugging
│
├── node_modules/
│   └── bootstrap/             # Bootstrap 5.3 source
│
├── package.json              # npm configuration
└── README.md                 # This file
```

---

## 🛠️ Development

### Edit Brand Colors

Edit `scss/_variables.scss` to change brand colors:

```scss
$primary: $depth;       // Changes all primary elements
$secondary: $bloom;     // Changes all secondary elements
$danger: $coral;        // Changes all danger alerts
```

### Add Custom Styles

Add custom CSS to `scss/_custom.scss`:

```scss
// Custom Notioneers components
.notioneers-card {
  border-radius: 0.5rem;
  border: 1px solid $sage;
  background: $halo;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}
```

### Modify Typography

Edit `scss/_fonts.scss` to change fonts:

```scss
$font-family-base: 'TWK Lausanne', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
```

---

## 📦 Build Process

### How it works:

```
1. npm run build
   ↓
2. Sass compiles:
   - _fonts.scss
   - _variables.scss
   - Bootstrap (from node_modules)
   - _custom.scss
   ↓
3. Output: css/theme.css
```

### File: `scss/theme.scss`

```scss
// 1. Import fonts
@import 'fonts';

// 2. Import variables (before Bootstrap)
@import 'variables';

// 3. Import Bootstrap
@import '../node_modules/bootstrap/scss/bootstrap';

// 4. Import custom styles (after Bootstrap)
@import 'custom';
```

This order ensures:
- Bootstrap uses your brand colors
- Custom styles override Bootstrap if needed

---

## 🎯 Using Bootstrap Classes

Once compiled, you can use Bootstrap classes with Notioneers colors:

```html
<!-- Primary color (depth: #063312) -->
<button class="btn btn-primary">Primary Button</button>

<!-- Secondary color (bloom: #92EF9A) -->
<button class="btn btn-secondary">Secondary Button</button>

<!-- Custom spacing -->
<div class="p-4 m-3">Padding & Margin</div>

<!-- Responsive grid -->
<div class="row">
  <div class="col-md-6">Half Width on Desktop</div>
  <div class="col-md-6">Half Width on Desktop</div>
</div>
```

---

## 📱 Responsive Design

Bootstrap 5 breakpoints (unchanged):

```scss
$grid-breakpoints: (
  xs: 0,
  sm: 576px,
  md: 768px,
  lg: 992px,
  xl: 1200px,
  xxl: 1400px
);
```

Use with classes:

```html
<!-- Responsive classes -->
<div class="col-12 col-md-6 col-lg-4">
  Responsive layout
</div>
```

---

## 🔍 Debugging

### View Source Map

When you run `npm run build`, a `theme.css.map` is generated.

In browser DevTools:
1. Open DevTools (F12)
2. Find the CSS rule
3. Click on file name
4. See original SCSS source

---

## 📚 Resources

- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [Sass/SCSS Documentation](https://sass-lang.com/documentation)
- [Notioneers Brand Guide](https://notioneers.eu/en/ai-style-guide)

---

## 🔄 Updating Bootstrap

To update Bootstrap version:

```bash
npm install bootstrap@latest
npm run build
```

This will use the new Bootstrap version with your custom colors.

---

## 📝 Contributing

When modifying the design system:

1. Edit SCSS files in `scss/`
2. Run `npm run build` to compile
3. Test in your app
4. Commit CSS changes

```bash
git add scss/
git add css/theme.css
git commit -m "design: Update brand colors/styles"
```

---

## 🚀 Using in Your App

### Option 1: Direct Link (Recommended)

```html
<!-- In admintool/views/layout.php -->
<link rel="stylesheet" href="../../shared/components/design-system/css/theme.css">
```

### Option 2: Copy CSS

If you need offline access, copy `css/theme.css` to your app.

### Option 3: Build Locally

If your app has npm, you can:

```bash
npm install ../../../shared/components/design-system
```

Then import:

```html
<link rel="stylesheet" href="node_modules/notioneers-design-system/css/theme.css">
```

---

## ✨ Status

- ✅ Bootstrap 5.3 integrated
- ✅ Notioneers colors defined
- ✅ Custom components ready
- ✅ Typography configured
- ✅ Build scripts ready

---

## 📞 Support

- **Bootstrap questions?** → [Bootstrap Docs](https://getbootstrap.com/docs/5.3/)
- **Color questions?** → Edit `scss/_variables.scss`
- **Build issues?** → Check `package.json` and run `npm install`

---

**Happy styling! 🎨**
