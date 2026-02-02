<?php
namespace ArBricks\Features;

use ArBricks\Options;

class Feature_Arbricks_Copy implements Feature_Interface {

    public static function id(): string {
        return 'arbricks_copy';
    }

    public static function meta(): array {
        return [
            'title'       => __( 'AR Copy to Clipboard', 'arbricks' ),
            'description' => __( 'نسخ أي نص إلى الحافظة عند الضغط على عنصر يحتوي على data-arbricks-copy.', 'arbricks' ),
            'category'    => 'tools',
            'help'        => [
                'summary'  => __( 'Allows you to copy any text to the clipboard by clicking on an HTML element. Great for sharing links, promo codes, or snippets.', 'arbricks' ),
                'how_to'   => [
                    __( 'Add the attribute `data-arbricks-copy="YOUR_TEXT"` to any HTML element (button, span, div).', 'arbricks' ),
                    __( 'Optionally customize the success and error messages in the settings below.', 'arbricks' ),
                ],
                'examples' => [
                    '<code>&lt;button data-arbricks-copy="https://arbricks.net"&gt;Copy Website Link&lt;/button&gt;</code>',
                ],
                'notes'    => [
                    __( 'This feature requires a secure connection (HTTPS) in most modern browsers.', 'arbricks' ),
                    __( 'The element\'s content will temporarily change to the success/error message after clicking.', 'arbricks' ),
                ],
            ],
        ];
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema(): array {
        return [
            'default_text' => [
                'type'        => 'text',
                'label'       => __( 'Default Text (Fallback)', 'arbricks' ),
                'description' => __( 'Text used if the element has no original content.', 'arbricks' ),
                'default'     => __( 'نسخ', 'arbricks' ),
            ],
            'success_text' => [
                'type'        => 'text',
                'label'       => __( 'Success Message', 'arbricks' ),
                'description' => __( 'Message shown after successful copy.', 'arbricks' ),
                'default'     => __( 'تم النسخ', 'arbricks' ),
            ],
            'error_text'   => [
                'type'        => 'text',
                'label'       => __( 'Error Message', 'arbricks' ),
                'description' => __( 'Message shown if copy fails.', 'arbricks' ),
                'default'     => __( 'فشل النسخ', 'arbricks' ),
            ],
        ];
    }

    public function register_hooks(): void {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function enqueue_scripts(): void {
        $settings = Options::get_feature_settings( self::id() );

        $defaults = [
            'default_text' => __( 'نسخ', 'arbricks' ),
            'success_text' => __( 'تم النسخ', 'arbricks' ),
            'error_text'   => __( 'فشل النسخ', 'arbricks' ),
        ];

        $config = wp_parse_args( $settings, $defaults );

        wp_enqueue_script(
            'arbricks-copy',
            ARBRICKS_PLUGIN_URL . 'assets/js/arbricks-copy.js',
            [],
            ARBRICKS_VERSION,
            true
        );

        wp_localize_script(
            'arbricks-copy',
            'ArBricksCopyConfig',
            [
                'defaultText' => $config['default_text'],
                'successText' => $config['success_text'],
                'errorText'   => $config['error_text'],
            ]
        );
    }

    public function render_admin_ui(): void {
        // Uses default UI (get_settings_schema)
    }
}