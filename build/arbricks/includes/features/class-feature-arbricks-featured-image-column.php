<?php
/**
 * Feature: Featured Image Column
 *
 * Adds a column to display the featured image in admin post lists.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

use ArBricks\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Featured_Image_Column
 */
class Feature_ArBricks_Featured_Image_Column implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_featured_image_column';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Featured Image Column', 'arbricks' ),
			'description' => __( 'Add a column to display the featured image in admin post and page lists.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary'  => __( 'Helps you visually review content faster by displaying a thumbnail of the post directly in the "All Posts" or "All Pages" list.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature and select the post types where you want the column to appear.', 'arbricks' ),
					__( 'You can choose the image size (Thumbnail or Medium).', 'arbricks' ),
					__( 'The new column will appear at the beginning of the list for easy viewing.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'This feature only appears in the admin panel and does not affect site speed for visitors.', 'arbricks' ),
					__( 'If the post has no featured image, a default icon will appear (if enabled).', 'arbricks' ),
				),
			),
		);
	}

	/**
	 * Get settings schema
	 *
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'arbricks_fic_post_types'      => array(
				'type'        => 'checkbox_group',
				'label'       => __( 'Post Types', 'arbricks' ),
				'description' => __( 'Select which lists to add the column to.', 'arbricks' ),
				'options'     => array(
					'post' => __( 'Posts', 'arbricks' ),
					'page' => __( 'Pages', 'arbricks' ),
				),
				'default'     => array( 'post', 'page' ),
			),
			'arbricks_fic_thumb_size'      => array(
				'type'        => 'select',
				'label'       => __( 'Thumbnail Size', 'arbricks' ),
				'description' => __( 'Choose the image size to be loaded in the column.', 'arbricks' ),
				'options'     => array(
					'thumbnail' => __( 'Thumbnail (Default)', 'arbricks' ),
					'medium'    => __( 'Medium', 'arbricks' ),
				),
				'default'     => 'thumbnail',
			),
			'arbricks_fic_show_placeholder' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Show Placeholder Icon', 'arbricks' ),
				'description' => __( 'Display a default icon if no featured image is set.', 'arbricks' ),
				'default'     => true,
			),
		);
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! is_admin() ) {
			return;
		}

		$settings   = Options::get_feature_settings( self::id() );
		$post_types = $settings['arbricks_fic_post_types'] ?? array();

		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			// Add column.
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_featured_image_column' ) );
			// Render content.
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_featured_image_column' ), 10, 2 );
		}

		// Styling.
		add_action( 'admin_head', array( $this, 'inject_column_styles' ) );
	}

	/**
	 * Add the Featured Image column to the end of the list.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_featured_image_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( 'title' === $key ) {
				$new_columns['arbricks_featured_image'] = __( 'Image', 'arbricks' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	/**
	 * Render the content of the Featured Image column.
	 *
	 * @param string $column_name Column ID.
	 * @param int    $post_id     Post ID.
	 */
	public function render_featured_image_column( $column_name, $post_id ): void {
		if ( 'arbricks_featured_image' !== $column_name ) {
			return;
		}

		$settings    = Options::get_feature_settings( self::id() );
		$size        = $settings['arbricks_fic_thumb_size'] ?? 'thumbnail';
		$placeholder = $settings['arbricks_fic_show_placeholder'] ?? true;

		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, $size, array( 'style' => 'width: 50px; height: auto; border-radius: 4px;' ) );
		} elseif ( $placeholder ) {
			echo '<span class="dashicons dashicons-format-image" style="font-size: 30px; width: 30px; height: 30px; color: #ccd0d4; display: block; margin: 0 auto;"></span>';
		} else {
			echo '&mdash;';
		}
	}

	/**
	 * Inject minimal CSS for the column.
	 */
	public function inject_column_styles(): void {
		?>
		<style>
			.fixed .column-arbricks_featured_image {
				width: 70px;
				text-align: center;
				vertical-align: middle;
			}
			.column-arbricks_featured_image img {
				display: block;
				margin: 0 auto;
			}
		</style>
		<?php
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
