=== ArBricks - بريكس بالعربي ===
Contributors: arbricks
Tags: arabic, rtl, tools, security, woocommerce
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.10
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional WordPress toolkit with security features, WooCommerce enhancements, and developer tools. RTL/LTR optimized.

== Description ==

ArBricks is a comprehensive WordPress plugin that provides a modular system for adding professional features to your WordPress site. The plugin includes security hardening, WooCommerce optimizations, SEO tools, and utility features.

**Core Features:**

= Security & Privacy =
* **Login Protection** - Math CAPTCHA and Google reCAPTCHA v2 for login forms
* **Login Honeypot** - Silent bot trap for login pages
* **XML-RPC Control** - Disable XML-RPC to prevent brute force attacks
* **WordPress Version Hiding** - Remove version indicators
* **Head Cleanup** - Remove unnecessary WordPress headers
* **Admin Bar Control** - Hide admin bar for non-administrators

= WooCommerce Extensions =
* **Free Checkout Optimization** - Minimize fields for free products
* **Cart Auto-Update** - Real-time cart updates
* **Direct Checkout** - Skip cart for single items
* **Free Price Labels** - Customize "Free" text display
* **Minimum Order Amount** - Enforce order minimums

= SEO & Marketing Tools =
* **Google Tag Manager** - Easy GTM integration
* **SEO Visibility Warning** - Alert when search engine visibility is off
* **Media Auto-Meta** - Auto-generate alt text and titles for uploads

= Developer Tools =
* **QR Code Generator** - Create QR codes for URLs (admin tool)
* **Email Shortcode** - Spam-protected mailto links via [email] shortcode

= Technical Features =
* **Auto-Discovery Architecture** - Automatically registers new features
* **Clean Uninstall** - Optional complete data removal
* **RTL/LTR Support** - Full bidirectional language support
* **Translation-Ready** - Fully internationalized

**Privacy & External Services**

This plugin provides optional features that may connect to external services:

* **Google reCAPTCHA** (optional) - When enabled, sends user IP addresses to Google for verification. Requires Google reCAPTCHA API keys. [Privacy Policy](https://policies.google.com/privacy)
* **Google Tag Manager** (optional) - When enabled, loads JavaScript from google.com and may track user behavior based on your GTM configuration. [Privacy Policy](https://policies.google.com/privacy)

These features are disabled by default and require user configuration to activate. No personal data is collected or transmitted unless you explicitly enable and configure these features.

== Installation ==

= Automatic Installation =
1. Log in to your WordPress dashboard
2. Navigate to Plugins → Add New
3. Search for "ArBricks"
4. Click "Install Now" and then "Activate"

= Manual Installation =
1. Download the plugin ZIP file
2. Log in to your WordPress dashboard
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. Activate the plugin after installation

= After Activation =
1. Navigate to ArBricks in the admin menu
2. Enable features you need by toggling switches
3. Configure feature settings as required
4. Click "Save Changes"

== Frequently Asked Questions ==

= Is this plugin compatible with WooCommerce? =

Yes! ArBricks includes several WooCommerce-specific features. They only activate when WooCommerce is installed and will not interfere with your store if WooCommerce is not present.

= Does this plugin slow down my website? =

No. ArBricks is designed for performance:
* Features are only loaded when enabled
* Minimal database queries (uses WordPress Options API)
* No custom database tables
* Conditional asset loading

= Can I remove all plugin data when uninstalling? =

Yes. In the ArBricks settings, you can enable "Delete all data on uninstall". When enabled, uninstalling the plugin will remove all settings, options, and temporary files. This option is disabled by default to protect your settings during reinstallation.

= Is this plugin translation-ready? =

Yes! ArBricks is fully internationalized and ready for translation. The plugin includes English and Arabic translations.

= Do I need a Google account to use this plugin? =

No. Google reCAPTCHA and Google Tag Manager are optional features. All other features work without any external accounts or services.

= Is this plugin GDPR compliant? =

The plugin itself does not collect any personal data. However:
* If you enable Google reCAPTCHA, user IP addresses are sent to Google
* If you enable Google Tag Manager, tracking depends on your GTM configuration

Always review your privacy policy when enabling features that use external services.

= Can I use this on a multisite network? =

Yes. ArBricks is multisite-compatible. Settings are configured per-site, not network-wide.

== Screenshots ==

1. Main settings page with feature cards
2. Feature toggle and configuration options
3. Security features section
4. WooCommerce optimizations
5. QR Code Generator tool

== Changelog ==

= 2.0.0 - 2024-02-02 =
* Major architectural refactor
* Added auto-discovery feature system
* Implemented clean separation: Features vs Snippets
* Added Google reCAPTCHA v2 for login
* Added Login Honeypot feature
* Added SEO Visibility Warning
* Added Media Auto-Meta generation
* Added Email shortcode for spam protection
* Improved admin UI with collapsible help sections
* Full RTL/LTR support with CSS logical properties
* Enhanced privacy controls
* Improved uninstall process with user-controlled data deletion
* WordPress Coding Standards compliance

= 1.x =
* Legacy version (pre-refactor)
* Basic snippet system

== Upgrade Notice ==

= 2.0.0 =
Major update with improved architecture and new features. Settings will be automatically migrated from v1.x. Backup your database before upgrading.

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

All features using external services are:
- Disabled by default
- Require manual configuration
- Clearly labeled in the settings interface

Site administrators are responsible for updating their privacy policy to reflect the use of these features if enabled.

== Support ==

For support, please visit: https://arbricks.net/

== Credits ==

* Developed by Majed | ArBricks
* Uses QRCode.js for client-side QR code generation
* Compatible with WordPress Coding Standards
