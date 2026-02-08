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
            'description' => __( 'Copy text to clipboard when clicking any element with data-arbricks-copy', 'arbricks' ),
            'category'    => 'tools',
            'help'        => [
                'summary'  => __( 'Allows you to copy any text to the clipboard by clicking on an HTML element. This is useful for sharing links, discount codes, or any specific text snippets.', 'arbricks' ),
                'how_to'   => [
                    __( 'Add the attribute <code>data-arbricks-copy="TEXT"</code> to any HTML element.', 'arbricks' ),
                    __( 'Customize the success and error messages in the settings below if needed.', 'arbricks' ),
                    __( 'The element\'s content will change to the success message for 1.5 seconds after a successful copy.', 'arbricks' ),
                ],
                'examples' => [
                    '<code>&lt;button data-arbricks-copy="Your Value Here"&gt;Copy&lt;/button&gt;</code>',
                ],
                'notes'    => [
                    __( 'This feature relies on the Clipboard API and requires a secure connection (HTTPS) in most modern browsers.', 'arbricks' ),
                    __( 'If the browser does not support the Clipboard API, the error message will be shown.', 'arbricks' ),
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
                'label'       => __( 'Default Text (on Failure)', 'arbricks' ),
                'description' => __( 'Text used if the element has no original content.', 'arbricks' ),
                'default'     => '',
                'placeholder' => __( 'Copy', 'arbricks' ),
            ],
            'success_text' => [
                'type'        => 'text',
                'label'       => __( 'Success message', 'arbricks' ),
                'description' => __( 'Message that appears after successful copying.', 'arbricks' ),
                'default'     => '',
                'placeholder' => __( 'Copied', 'arbricks' ),
            ],
            'error_text'   => [
                'type'        => 'text',
                'label'       => __( 'Error message', 'arbricks' ),
                'description' => __( 'Message that appears when copying fails.', 'arbricks' ),
                'default'     => '',
                'placeholder' => __( 'Copy failed', 'arbricks' ),
            ],
        ];
    }

    public function register_hooks(): void {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function enqueue_scripts(): void {
        $settings = Options::get_feature_settings( self::id() );

        $defaults = [
            'default_text' => __( 'Copy', 'arbricks' ),
            'success_text' => __( 'Copied', 'arbricks' ),
            'error_text'   => __( 'Copy failed', 'arbricks' ),
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
                'defaultText' => ! empty( $config['default_text'] ) ? $config['default_text'] : __( 'Copy', 'arbricks' ),
                'successText' => ! empty( $config['success_text'] ) ? $config['success_text'] : __( 'Copied', 'arbricks' ),
                'errorText'   => ! empty( $config['error_text'] ) ? $config['error_text'] : __( 'Copy failed', 'arbricks' ),
            ]
        );
    }

    public function render_admin_ui(): void {
        // Uses default UI (get_settings_schema)
    }
}