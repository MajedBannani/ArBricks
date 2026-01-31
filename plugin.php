<?php
/**
 * Plugin Name:     بريكس بالعربي
 * Description:     استمتع بأسهل طريقة لإضافة أنماط وأدوات احترافية إلى موقع ووردبريس الخاص بك
 * Author:          Majed |<a href="https://arbricks.net/" >ArBricks</a>
 * Version:         1.1.2
 */

if (!defined('ABSPATH')) {
    die;
}

// Core files
include_once __DIR__ . '/WFPCore/WordPressContext.php';

// Define default features
function arbricks_get_default_features() {
    return array(
        'qr_generator' => false,
        'webp_converter' => false,
        'youtube_timestamp' => false,
        'css_minifier' => false,
        'grid_layout' => false,    
        'auto_grid' => false,      
        'blob_css' => false,       
        'noise_css' => false       
    );
}

// Add settings menu
add_action('admin_menu', 'arbricks_add_settings_page');
add_action('admin_init', 'arbricks_register_settings');
add_action('admin_enqueue_scripts', 'arbricks_admin_styles');

function arbricks_admin_styles($hook) {
    if ('toplevel_page_arbricks-settings' !== $hook) {
        return;
    }
    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Add custom styles and scripts
    wp_add_inline_style('admin-bar', '
        .arbricks-wrap {
            max-width: 900px;
            margin: 20px;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .arbricks-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .arbricks-header h1 {
            color: #1d2327;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e4e7;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .feature-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .feature-title {
            font-size: 1.2em;
            color: #1d2327;
            margin: 0;
        }
        
        .feature-description {
            color: #666;
            margin: 10px 0;
            font-size: 0.95em;
        }
        
        .shortcode-box {
            background: #e9ecef;
            border-radius: 6px;
            padding: 10px 15px;
            font-family: monospace;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            direction: ltr;
            margin-top: 10px;
        }
        
        .shortcode-box:hover {
            background: #dee2e6;
        }
        
        .shortcode-text {
            color: #495057;
            font-size: 14px;
        }
        
        .copy-icon {
            color: #6c757d;
            cursor: pointer;
        }
        
        .copy-icon:hover {
            color: #495057;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #2196F3;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .success-message {
            display: none;
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
    ');
    
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $(".shortcode-box").click(function() {
                var shortcode = $(this).find(".shortcode-text").text();
                navigator.clipboard.writeText(shortcode).then(function() {
                    var successMsg = $(this).next(".success-message");
                    successMsg.fadeIn().delay(2000).fadeOut();
                }.bind(this));
            });
        });
    ');
}

function arbricks_add_settings_page() {
    add_menu_page(
        'إعدادات بريكس بالعربي',
        'بريكس بالعربي',
        'manage_options',
        'arbricks-settings',
        'arbricks_settings_page_content',
        'dashicons-admin-generic'
    );
}

function arbricks_register_settings() {
    register_setting('arbricks_settings', 'arbricks_features');
    
    if (false === get_option('arbricks_features')) {
        update_option('arbricks_features', arbricks_get_default_features());
    }
}

function arbricks_settings_page_content() {
    $features = wp_parse_args(get_option('arbricks_features'), arbricks_get_default_features());
    ?>
    <div class="wrap arbricks-wrap" style="direction: rtl;">
        <div class="arbricks-header">
            <h1>إعدادات بريكس بالعربي</h1>
            <p class="description">قم بتفعيل وتعطيل الميزات التي تحتاجها</p>
        </div>
        
        <form method="post" action="options.php">
            <?php settings_fields('arbricks_settings'); ?>
            
            <div class="feature-cards">
                <!-- QR Generator -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">منشئ رموز QR</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[qr_generator]" 
                                   value="1" <?php checked(isset($features['qr_generator']) && $features['qr_generator']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php if (isset($features['qr_generator']) && $features['qr_generator']): ?>
                        <div class="shortcode-box">
                            <span class="shortcode-text">[qr-generator]</span>
                            <span class="copy-icon dashicons dashicons-clipboard"></span>
                        </div>
                        <div class="success-message">تم نسخ الشورت كود!</div>
                    <?php endif; ?>
                </div>

                <!-- WebP Converter -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">محول الصور إلى WebP</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[webp_converter]" 
                                   value="1" <?php checked(isset($features['webp_converter']) && $features['webp_converter']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php if (isset($features['webp_converter']) && $features['webp_converter']): ?>
                        <div class="shortcode-box">
                            <span class="shortcode-text">[webp-converter]</span>
                            <span class="copy-icon dashicons dashicons-clipboard"></span>
                        </div>
                        <div class="success-message">تم نسخ الشورت كود!</div>
                    <?php endif; ?>
                </div>

                <!-- YouTube Timestamp -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">منشئ روابط يوتيوب مع الوقت</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[youtube_timestamp]" 
                                   value="1" <?php checked(isset($features['youtube_timestamp']) && $features['youtube_timestamp']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php if (isset($features['youtube_timestamp']) && $features['youtube_timestamp']): ?>
                        <div class="shortcode-box">
                            <span class="shortcode-text">[youtube-generator]</span>
                            <span class="copy-icon dashicons dashicons-clipboard"></span>
                        </div>
                        <div class="success-message">تم نسخ الشورت كود!</div>
                    <?php endif; ?>
                </div>

                <!-- CSS Minifier -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">مصغر أكواد CSS</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[css_minifier]" 
                                   value="1" <?php checked(isset($features['css_minifier']) && $features['css_minifier']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php if (isset($features['css_minifier']) && $features['css_minifier']): ?>
                        <div class="shortcode-box">
                            <span class="shortcode-text">[css-minifier]</span>
                            <span class="copy-icon dashicons dashicons-clipboard"></span>
                        </div>
                        <div class="success-message">تم نسخ الشورت كود!</div>
                    <?php endif; ?>
                </div>

                <!-- Grid Layout -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">Grid Layout CSS</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[grid_layout]" 
                                   value="1" <?php checked(isset($features['grid_layout']) && $features['grid_layout']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">نظام شبكة متجاوب لتصميم الصفحات</p>
                </div>

                <!-- Auto Grid -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">Auto Grid CSS</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[auto_grid]" 
                                   value="1" <?php checked(isset($features['auto_grid']) && $features['auto_grid']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">شبكة تلقائية التكيف مع حجم الشاشة</p>
                </div>

                <!-- Blob CSS -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">Blob Effect CSS</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[blob_css]" 
                                   value="1" <?php checked(isset($features['blob_css']) && $features['blob_css']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">تأثير الشكل المتحرك</p>
                </div>

                <!-- Noise Effect -->
                <div class="feature-card">
                    <div class="feature-header">
                        <h3 class="feature-title">Noise Effect</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="arbricks_features[noise_css]" 
                                   value="1" <?php checked(isset($features['noise_css']) && $features['noise_css']); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="feature-description">تأثير الضوضاء</p>
                </div>
            </div>

            <?php submit_button('حفظ التغييرات', 'primary', 'submit', true, array('style' => 'margin-top: 20px;')); ?>
        </form>
    </div>
    <?php
}

// Get features with default values as fallback
$features = wp_parse_args(get_option('arbricks_features'), arbricks_get_default_features());

// Conditional loading of features based on settings
if (isset($features['qr_generator']) && $features['qr_generator']) {
    include_once 'snippets/qr_code_generator.php';
}

if (isset($features['webp_converter']) && $features['webp_converter']) {
    include_once 'snippets/image_to_webp_converter.php';
}

if (isset($features['youtube_timestamp']) && $features['youtube_timestamp']) {
    include_once 'snippets/youtube.php';
}

if (isset($features['css_minifier']) && $features['css_minifier']) {
    include_once 'snippets/css_minifier.php';
}


if (isset($features['grid_layout']) && $features['grid_layout']) {
    include_once 'snippets/arbricks_grid_layout_inline_css.php';
}


if (isset($features['auto_grid']) && $features['auto_grid']) {
    include_once 'snippets/arbricks_auto_grid_inline_css.php';
}


if (isset($features['blob_css']) && $features['blob_css']) {
    include_once 'snippets/arbricks_blob_inline_css.php';
}

if (isset($features['noise_css']) && $features['noise_css']) {
    include_once 'snippets/arbricks_noise_inline_css.php';
}