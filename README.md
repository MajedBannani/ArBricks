# ArBricks WordPress Plugin

**Version:** 2.0.0  
**Requires PHP:** 7.4+  
**WordPress Compatible:** 5.8+

## Overview

ArBricks is a WordPress plugin that provides a modular snippet system for adding professional styles and tools to your WordPress site. The plugin features a clean, RTL/LTR-friendly admin interface and follows WordPress Coding Standards.

## Features

### Tools
- **QR Code Generator** - Generate QR codes for any URL
- **WebP Converter** - Convert images to WebP format
- **YouTube Timestamp Generator** - Create YouTube links with timestamps
- **CSS Minifier** - Minify CSS code

### Styles
- **Grid Layout CSS** - Responsive grid layout system
- **Auto Grid CSS** - Automatic adaptive grid
- **Blob Effect CSS** - Animated blob backgrounds
- **Noise Effect** - Grainy texture overlay

## Architecture

The plugin uses a modern, object-oriented architecture:

```
/arbricks.php                    Bootstrap file
/includes
  /class-plugin.php              Main orchestrator (singleton)
  /class-options.php             Data layer with migration
  /class-admin.php               Admin interface
  /snippets
    /interface-snippet.php       Snippet contract
    /abstract-snippet.php        Base snippet class
    /class-snippet-registry.php  Registry & discovery
    /built-in                    Built-in snippet classes
```

## Adding a New Snippet

Creating a new snippet is simple - just add a class file!

### Step 1: Create your snippet class

Create a new file in `/includes/snippets/built-in/class-my-snippet.php`:

```php
<?php
namespace ArBricks\Snippets\Built_In;

use ArBricks\Snippets\Abstract_Snippet;

class My_Snippet extends Abstract_Snippet {
    
    public function get_id() {
        return 'my_snippet';  // Unique ID
    }
    
    public function get_label() {
        return __( 'My Snippet', 'arbricks' );
    }
    
    public function get_description() {
        return __( 'Description of what my snippet does', 'arbricks' );
    }
    
    public function get_category() {
        return 'tools';  // or 'styles'
    }
    
    public function apply() {
        // Your snippet logic here
        // Examples:
        
        // For shortcodes:
        $this->register_shortcode( 'my-shortcode', array( $this, 'render' ) );
        
        // For inline CSS:
        $this->add_inline_css( '.my-class { color: red; }', 'my-snippet' );
    }
    
    public function render( $atts, $content = '' ) {
        // Shortcode rendering logic
        return '<div>My content</div>';
    }
}
```

### Step 2: That's it!

The snippet will be automatically discovered and registered. No need to modify any core files!

## Development Setup

### Install Dependencies

```bash
composer install
```

### Code Linting

Check code standards:
```bash
composer run lint
```

Auto-fix code standards:
```bash
composer run lint:fix
```

## Security

All admin actions include:
- Nonce verification
- Capability checks (`manage_options`)
- Input sanitization
- Output escaping

## RTL/LTR Support

The admin interface uses CSS logical properties throughout:
- `margin-inline`, `padding-inline`
- `inset-inline-start`, `inset-inline-end`
- `inline-size`, `block-size`
- `text-align: start/end`

This ensures perfect rendering in both RTL and LTR languages.

## Migration from v1.x

The plugin automatically migrates old settings on first activation:
- Old option: `arbricks_features` (flat array)
- New option: `arbricks_options` (structured array with version)
- A backup is created: `arbricks_features_backup`

## License

GPL v2 or later

## Author

Majed | [ArBricks](https://arbricks.net/)
