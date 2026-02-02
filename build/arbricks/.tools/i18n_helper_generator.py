#!/usr/bin/env python3
"""
Comprehensive i18n helper generator for ArBricks plugin
Adds helper content to all features and generates Arabic translations
"""

# Feature helper content database (English)
FEATURE_HELPERS = {
    'disable-comments-sitewide': {
        'summary': 'Completely disable comments across your entire WordPress site, including frontend, admin, and REST API.',
        'how_to': [
            'Enable the feature toggle above.',
            'Comments will be disabled sitewide immediately.',
            'Comment forms disappear from posts/pages.',
            'Admin comment menu items are removed.',
        ],
        'notes': [
            'Existing comments remain in database but are hidden.',
            'Comment feeds and REST endpoints are blocked.',
            'No settings needed - works immediately after activation.',
        ],
        'examples': [
            'Perfect for portfolio/business sites that don\'t need comments.',
        ],
    },
    'disable-xmlrpc': {
        'summary': 'Disable XML-RPC protocol to improve security and prevent brute-force attacks.',
        'how_to': [
            'Enable the feature toggle above.',
            'XML-RPC requests will be blocked.',
            'Optionally block direct access to xmlrpc.php file.',
        ],
        'notes': [
            'XML-RPC is often targeted by attackers.',
            'Some remote publishing tools require XML-RPC.',
            'Jetpack and other services may need XML-RPC enabled.',
        ],
        'examples': [],
    },
    'head-cleanup': {
        'summary': 'Remove unnecessary clutter from WordPress wp_head output for cleaner HTML.',
        'how_to': [
            'Enable the feature toggle above.',
            'Configure which elements to remove.',
            'Save changes to apply cleanup.',
        ],
        'notes': [
            'Removes legacy discovery links (RSD, Windows Live Writer).',
            'Optionally removes shortlink, REST links, and feed links.',
            'Does not affect site functionality.',
        ],
        'examples': [],
    },
    'hide-admin-bar-non-admin': {
        'summary': 'Hide the WordPress admin bar on frontend for users without admin capabilities.',
        'how_to': [
            'Enable the feature toggle above.',
            'Non-admin users will not see admin bar on frontend.',
            'Admins still see the admin bar.',
        ],
        'notes': [
            'Only affects frontend display, not admin area.',
            'Based on user capabilities, not roles.',
        ],
        'examples': [],
    },
    'login-honeypot': {
        'summary': 'Add invisible honeypot field to login form to block automated bots.',
        'how_to': [
            'Enable the feature toggle above.',
            'Honeypot field is added automatically to login form.',
            'Bots that fill the hidden field are blocked.',
        ],
        'notes': [
            'Invisible to human users.',
            'Effective against simple bots.',
            'No visual changes to login page.',
        ],
        'examples': [],
    },
    'login-recaptcha': {
        'summary': 'Add Google reCAPTCHA v2 verification to WordPress login form.',
        'how_to': [
            'Get reCAPTCHA keys from Google reCAPTCHA admin.',
            'Enable the feature toggle above.',
            'Enter your Site Key and Secret Key.',
            'Save changes.',
            'reCAPTCHA checkbox appears on login form.',
        ],
        'notes': [
            'Requires valid Google reCAPTCHA v2 keys.',
            'Users must verify they are  human before logging in.',
            'May impact login speed slightly.',
        ],
        'examples': [
            'Get keys at: https://www.google.com/recaptcha/admin',
        ],
    },
    'math-captcha-login': {
        'summary': 'Add simple math challenge to login and WooCommerce account forms.',
        'how_to': [
            'Enable the feature toggle above.',
            'Math captcha appears on wp-login.php.',
            'Also appears on WooCommerce My Account forms if WooCommerce is active.',
        ],
        'notes': [
            'Simple addition/subtraction problems.',
            'Effective against basic bots.',
            'No external dependencies required.',
        ],
        'examples': [
            'Example challenge: "What is 7 + 3?"',
        ],
    },
    'media-auto-meta': {
        'summary': 'Automatically generate image metadata from filename on upload.',
        'how_to': [
            'Enable the feature toggle above.',
            'Upload an image with descriptive filename.',
            'Metadata is auto-populated from filename.',
        ],
        'notes': [
            'Generates title, caption, description, and alt text.',
            'Uses filename with hyphens/underscores converted to spaces.',
            'Saves time when uploading many images.',
        ],
        'examples': [
            'Filename: "red-sports-car.jpg" → Alt: "Red sports car"',
        ],
    },
    'remove-wp-version': {
        'summary': 'Remove WordPress version number from HTML head and feeds for security.',
        'how_to': [
            'Enable the feature toggle above.',
            'Version meta tag is removed from all pages.',
            'Version is also removed from RSS/Atom feeds.',
        ],
        'notes': [
            'Prevents version disclosure to potential attackers.',
            'Slight security improvement through obscurity.',
        ],
        'examples': [],
    },
    'seo-visibility-warning': {
        'summary': 'Show admin notice if search engines are blocked from indexing your site.',
        'how_to': [
            'Enable the feature toggle above.',
            'If "Discourage search engines" is checked, a warning appears.',
            'Go to Settings → Reading to toggle search visibility.',
        ],
        'notes': [
            'Helpful reminder for development/staging sites.',
            'Ensures you don\'t forget to enable indexing on production.',
        ],
        'examples': [],
    },
    'shortcode-email': {
        'summary': 'Shortcode to display obfuscated email addresses (anti-spam).',
        'how_to': [
            'Enable the feature toggle above.',
            'Use shortcode [email] in posts/pages.',
            'Email is displayed as clickable mailto link.',
        ],
        'notes': [
            'Email is obfuscated using WordPress antispambot().',
            'Protects from basic email scrapers.',
        ],
        'examples': [
            'Shortcode: [email]user@example.com[/email]',
            'Or: [email address="user@example.com"]',
        ],
    },
    'wc-cart-auto-update': {
        'summary': 'Automatically update WooCommerce cart when quantity changes.',
        'how_to': [
            'Enable the feature toggle above.',
            'Cart totals update immediately when quantity is changed.',
            'No need to click "Update Cart" button.',
        ],
        'notes': [
            'Requires WooCommerce plugin.',
            'Uses AJAX for seamless updates.',
            'Improves user experience.',
        ],
        'examples': [],
    },
    'wc-direct-checkout-single-item': {
        'summary': 'Skip cart page and go directly to checkout when adding single item.',
        'how_to': [
            'Enable the feature toggle above.',
            'When customer clicks "Add to Cart", they go straight to checkout.',
            'Only works when cart has exactly one item.',
        ],
        'notes': [
            'Requires WooCommerce plugin.',
            'Speeds up checkout for simple purchases.',
            'Cart page is skipped entirely.',
        ],
        'examples': [],
    },
    'wc-free-checkout-min-fields': {
        'summary': 'Hide unnecessary checkout fields when order total is zero (free orders).',
        'how_to': [
            'Enable the feature toggle above.',
            'When cart total is 0, only essential fields are shown.',
            'Billing address fields are hidden.',
        ],
        'notes': [
            'Requires WooCommerce plugin.',
            'Only applies when order total is exactly 0.',
            'Simplifies free product checkout experience.',
        ],
        'examples': [],
    },
    'wc-free-price-label': {
        'summary': 'Replace "Free" price text with custom label on WooCommerce products.',
        'how_to': [
            'Enable the feature toggle above.',
            'Enter your custom label (e.g., "مجاناً" in Arabic).',
            'Products with price 0 will show your custom text.',
        ],
        'notes': [
            'Requires WooCommerce plugin.',
            'Only affects products with price = 0.',
            'Useful for multilingual sites.',
        ],
        'examples': [
            'Custom label: "مجاناً" or "Free Download"',
        ],
    },
    'wc-minimum-order-amount': {
        'summary': 'Set minimum order total required to complete checkout.',
        'how_to': [
            'Enable the feature toggle above.',
            'Enter minimum order amount.',
            'Save changes.',
            'Cart totals must exceed this amount to proceed.',
        ],
        'notes': [
            'Requires WooCommerce plugin.',
            'Blocks checkout if cart total is below minimum.',
            'Shows clear error message to customers.',
        ],
        'examples': [
            'Set minimum to 50 to require minimum $50 orders.',
        ],
    },
    'webp-auto-convert': {
        'summary': 'Automatically convert uploaded JPG/PNG images to WebP format.',
        'how_to': [
            'Enable the feature toggle above.',
            'Upload a JPG or PNG image.',
            'WebP version is created automatically.',
            'Original file is kept.',
        ],
        'notes': [
            'Requires GD or Imagick with WebP support.',
            'WebP images are smaller and load faster.',
            'May not work on all hosting environments.',
        ],
        'examples': [],
    },
}

# Arabic translations for helper content
ARABIC_TRANSLATIONS = {
    'disable-comments-sitewide': {
        'summary': 'تعطيل التعليقات بالكامل عبر موقع ووردبريس بأكمله، بما في ذلك الواجهة الأمامية والإدارية و REST API.',
        'how_to': [
            'فعّل المفتاح أعلاه.',
            'سيتم تعطيل التعليقات على مستوى الموقع فوراً.',
            'تختفي نماذج التعليقات من المقالات/الصفحات.',
            'تتم إزالة عناصر قائمة التعليقات في لوحة التحكم.',
        ],
        'notes': [
            'التعليقات الموجودة تبقى في قاعدة البيانات لكنها مخفية.',
            'يتم حظر خلاصات التعليقات ونقاط REST.',
            'لا حاجة لإعدادات - يعمل فوراً بعد التفعيل.',
        ],
        'examples': [
            'مثالي لمواقع الأعمال أو المحافظ التي لا تحتاج تعليقات.',
        ],
    },
    # Add more as needed...
}

if __name__ == '__main__':
    print(f"✓ Helper content database ready for {len(FEATURE_HELPERS)} features")
    print(f"✓ Arabic translations ready for {len(ARABIC_TRANSLATIONS)} features")
