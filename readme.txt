=== ArBricks - Professional WordPress Toolkit ===
Contributors: arbricks
Tags: security, woocommerce, arabic, rtl, admin-tools
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.13
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional WordPress toolkit with 48+ modular features for security, WooCommerce optimization, admin enhancements, and developer tools.

== Description ==

**ArBricks** is a comprehensive, modular WordPress plugin that provides a professional toolkit for site administrators, developers, and WooCommerce store owners. Each feature can be independently enabled or disabled, giving you complete control over your site's functionality.

With 48+ features organized into 6 categories (Security, WooCommerce, SEO, Admin Tools, Performance, and Developer Tools), ArBricks empowers you to enhance your WordPress site without installing dozens of separate plugins.

**Key Highlights:**

* 🔒 **20+ Security Features** - Login protection, brute force prevention, security headers, file protection
* 🛒 **7 WooCommerce Enhancements** - Checkout optimization, cart improvements, admin invoice printing
* 🔍 **SEO & Audit Tools** - Spam detection, link auditing, visibility warnings
* ⚙️ **Admin Enhancements** - Dashboard cleanup, post ID display, custom branding, featured image columns
* ⚡ **Performance Optimizations** - Remove unused assets, disable unnecessary features
* 🛠️ **Developer Tools** - QR generator, markdown export, copy to clipboard, 2FA

= 🔐 Security Features =

* **Login Protection Suite**
  * Math CAPTCHA for login and WooCommerce
  * Google reCAPTCHA v2 integration
  * Login honeypot (silent bot trap)
  * Login rate limiting
  * Two-Factor Authentication (2FA) with TOTP
  * Login with email instead of username
  * Auto logout for inactive users

* **Attack Prevention**
  * Disable XML-RPC (prevents brute force attacks)
  * Disable user enumeration
  * Block PHP file uploads
  * Block image hotlinking
  * Limit login attempts with lockout

* **Hardening & Headers**
  * Security headers (X-Frame-Options, X-Content-Type-Options, etc.)
  * Remove WordPress version indicators
  * Hide sensitive WordPress files
  * Disable WLWManifest link
  * Head cleanup (remove unnecessary meta tags)
  * Disable RSS feeds

= 🛒 WooCommerce Features =

* **Checkout Optimization**
  * Minimal checkout fields for free products
  * Direct checkout for single-item carts
  * Real-time cart auto-update (AJAX)
  * Enforce minimum order amounts

* **Branding & Display**
  * Customize "Free" price labels
  * Print invoices from admin (thermal printer support)
  * RTL-ready invoice templates

= 🔍 SEO & Security Audit Tools =

* **SEO Spam Detection**
  * Content scan for hidden text and obfuscated code
  * External link audit with suspicious domain detection
  * Redirect anomaly check (bot vs. user comparison)

* **SEO Helpers**
  * Visibility warning when search engines are blocked
  * Auto-generate image alt text and titles on upload
  * Email spam protection via shortcode [email]

= ⚙️ Admin Enhancements =

* **Dashboard & Interface**
  * Clean dashboard (remove unwanted widgets)
  * Custom admin bar greeting
  * Custom admin footer text
  * Post status colors for easy identification
  * Featured image column in post lists
  * Last login timestamp column for users
  * Show post and page IDs

* **UI Improvements**
  * Hide admin bar for non-administrators
  * Fading page transitions
  * AR Copy to Clipboard tool
  * Disable comments sitewide

= ⚡ Performance Features =

* Remove Dashicons on frontend (for non-logged-in users)
* Remove Block Library CSS (if not using Gutenberg)
* Disable jQuery Migrate
* WebP auto-conversion on upload
* Arabic PDF support (font embedding)

= 🛠️ Developer Tools =

* QR Code Generator (admin tool)
* Markdown URL export for LLMs and AI tools
* Email shortcode with obfuscation
* Google Tag Manager integration

= 🌍 Translation & RTL Support =

* Fully translation-ready with English and Arabic translations included
* Complete RTL (Right-to-Left) support for Arabic and Hebrew
* Uses WordPress translation functions throughout

**Privacy & External Services**

This plugin provides optional features that may connect to external services:

* **Google reCAPTCHA** (optional) - When enabled, sends user IP addresses to Google for verification. Requires Google reCAPTCHA API keys. [Privacy Policy](https://policies.google.com/privacy)
* **Google Tag Manager** (optional) - When enabled, loads JavaScript from google.com and may track user behavior based on your GTM configuration. [Privacy Policy](https://policies.google.com/privacy)

These features are disabled by default and require user configuration to activate. No personal data is collected or transmitted unless you explicitly enable and configure these features.

== Installation ==

= Automatic Installation =

1. Log in to your WordPress dashboard
2. Navigate to **Plugins → Add New**
3. Search for "**ArBricks**"
4. Click "**Install Now**" and then "**Activate**"
5. Navigate to **ArBricks** in the admin menu
6. Enable the features you need

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress dashboard
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Choose the downloaded ZIP file and click "**Install Now**"
5. Activate the plugin after installation

= After Activation =

1. Go to **ArBricks** in the admin menu
2. Browse features organized by category (Security, WooCommerce, SEO, Admin, Performance, Tools)
3. Enable features by toggling switches
4. Configure feature settings (if applicable)
5. Click "**Save Changes**"

== Frequently Asked Questions ==

= How many features are included? =

ArBricks includes 48+ modular features covering security, WooCommerce, SEO, admin enhancements, performance, and developer tools. Each feature can be independently enabled or disabled.

= Is this plugin compatible with WooCommerce? =

Yes! ArBricks includes 7 WooCommerce-specific features. They only activate when WooCommerce is installed and will not interfere if WooCommerce is not present.

= Does this plugin slow down my website? =

No. ArBricks is designed for performance:
* Features are only loaded when enabled
* Minimal database queries (uses WordPress Options API)
* No custom database tables
* Conditional asset loading
* Uses transient caching where appropriate

= Which features require external services? =

Only 2 optional features use external services:
* **Google reCAPTCHA** - Requires API keys and sends user IPs to Google
* **Google Tag Manager** - Loads tracking JavaScript from Google

All other 46+ features work entirely on your server with no external dependencies.

= Can I remove all plugin data when uninstalling? =

Yes. In the ArBricks settings, you can enable "**Delete all data on uninstall**". When enabled, uninstalling the plugin will remove all settings, options, and temporary files. This option is disabled by default to protect your settings.

= Is this plugin translation-ready? =

Yes! ArBricks is fully internationalized and ready for translation. The plugin includes:
* English (default)
* Arabic (complete translation)
* POT file for custom translations

= Is this plugin GDPR compliant? =

The plugin itself does not collect any personal data. However:
* If you enable Google reCAPTCHA, user IP addresses are sent to Google
* If you enable Google Tag Manager, tracking depends on your GTM configuration
* Two-Factor Authentication stores encrypted secrets in user meta

Always review your privacy policy when enabling features that use external services.

= Can I use this on a multisite network? =

Yes. ArBricks is multisite-compatible. Settings are configured per-site, not network-wide.

= Does this work with Bricks Builder? =

The plugin name references "bricks" as building blocks, not the Bricks Builder theme. However, ArBricks is compatible with all WordPress themes including Bricks Builder.

= How is this different from other security plugins? =

ArBricks is not just a security plugin - it's a comprehensive toolkit. While it includes 20+ security features, it also provides WooCommerce enhancements, SEO tools, admin improvements, and developer utilities. You get multiple plugin functionalities in one modular package.

== Changelog ==

= 2.0.13 - 2026-02-13 =
* Fixed: Version synchronization between main file and readme
* Updated: WordPress compatibility to 6.7
* Improved: Removed unused code for better performance
* Added: .distignore file for cleaner distribution

= 2.0.0 - 2024-02-02 =
* Major architectural refactor
* Added auto-discovery feature system
* Implemented clean separation: Features vs Snippets
* Added 48+ modular features organized by category
* New Security Features:
  * Google reCAPTCHA v2 for login
  * Login Honeypot
  * Two-Factor Authentication (2FA)
  * Security Headers
  * Block PHP Uploads
  * Disable User Enumeration
  * Limit Login Attempts
* New SEO Tools:
  * SEO Spam Content Scan
  * SEO Spam Link Audit
  * Redirect Anomaly Check
  * SEO Visibility Warning
  * Media Auto-Meta generation
* New WooCommerce Features:
  * Print Order Invoice from Admin
  * WooCommerce CAPTCHA protection
* New Admin Tools:
  * Featured Image Column
  * Last Login Column
  * Post Status Colors
  * Clean Dashboard
  * Custom Admin Branding
* New Performance Features:
  * WebP Auto-Convert
  * Remove Dashicons Frontend
  * Remove Block Library CSS
  * Disable jQuery Migrate
* New Developer Tools:
  * QR Code Generator
  * Markdown URLs for LLMs
  * AR Copy to Clipboard
  * Email Shortcode
* Improved admin UI with collapsible help sections
* Full RTL/LTR support with CSS logical properties
* Enhanced privacy controls with external service disclosures
* Improved uninstall process with user-controlled data deletion
* WordPress Coding Standards compliance
* Complete English and Arabic translations

= 1.x =
* Legacy version (pre-refactor)
* Basic snippet system

== Upgrade Notice ==

= 2.0.13 =
Minor maintenance update. Fixes version synchronization and removes unused code. Safe to upgrade.

= 2.0.0 =
Major update with 48+ modular features and improved architecture. Settings will be automatically migrated from v1.x. Backup your database before upgrading.

== Privacy Policy ==

This plugin does not collect, store, or transmit any personal data by default.

**Optional External Services:**

When you enable certain features, the plugin may transmit data to third-party services:

1. **Google reCAPTCHA** (Login Protection Feature)
   - What is sent: User IP address, browser information
   - Purpose: Bot detection and spam prevention
   - Service provider: Google LLC
   - Privacy policy: https://policies.google.com/privacy
   - User control: Feature must be manually enabled and configured

2. **Google Tag Manager** (Marketing Feature)
   - What is sent: Depends on your GTM container configuration
   - Purpose: Analytics and marketing tracking (as configured by you)
   - Service provider: Google LLC
   - Privacy policy: https://policies.google.com/privacy
   - User control: Feature must be manually enabled and configured

3. **Two-Factor Authentication** (Security Feature)
   - What is stored: Encrypted TOTP secrets in WordPress user meta
   - Purpose: Enhanced login security
   - Storage location: Your WordPress database
   - User control: Per-user opt-in

All features using external services are:
- Disabled by default
- Require manual configuration
- Clearly labeled in the settings interface
- Documented with privacy policy links

Site administrators are responsible for updating their privacy policy to reflect the use of these features if enabled.

== Support ==

For support, documentation, and updates, please visit: https://arbricks.net/

== Credits ==

* Developed by Majed | ArBricks
* Uses QRCode.js for client-side QR code generation (MIT License)
* Compatible with WordPress Coding Standards
* Full RTL support for Arabic and Hebrew languages
