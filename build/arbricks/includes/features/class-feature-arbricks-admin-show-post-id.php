<?php
/**
 * Feature: Admin Show Post ID
 *
 * Adds an ID column to the admin list tables for posts and pages.
 *
 * @package ArBricks
 * @since 2.1.0
 */

namespace ArBricks\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Feature_ArBricks_Admin_Show_Post_ID
 */
class Feature_ArBricks_Admin_Show_Post_ID implements Feature_Interface {

	/**
	 * Get feature ID
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'arbricks_admin_show_post_id';
	}

	/**
	 * Get feature metadata
	 *
	 * @return array
	 */
	public static function meta(): array {
		return array(
			'title'       => __( 'Show Post and Page IDs', 'arbricks' ),
			'description' => __( 'Adds a column to display the ID of posts and pages in admin tables for easier development.', 'arbricks' ),
			'category'    => 'tools',
			'help'        => array(
				'summary'  => __( 'Adds a new column to admin tables displaying the unique ID for each post or page, making it easier to work with shortcodes or during development.', 'arbricks' ),
				'how_to'   => array(
					__( 'Enable the feature toggle above.', 'arbricks' ),
					__( 'Navigate to any post or page list and a new "ID" column will appear.', 'arbricks' ),
				),
				'notes'    => array(
					__( 'Works on post and page lists by default.', 'arbricks' ),
					__( 'This column is for display only and is not sortable.', 'arbricks' ),
					__( 'Note: If another plugin already shows IDs, you might see duplicate columns. Disable one to avoid clutter.', 'arbricks' ),
				),
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

		// Posts.
		add_filter( 'manage_posts_columns', array( $this, 'add_id_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_id_column_value' ), 10, 2 );

		// Pages.
		add_filter( 'manage_pages_columns', array( $this, 'add_id_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'render_id_column_value' ), 10, 2 );
	}

	/**
	 * Add ID column to the columns array
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_id_column( $columns ) {
		// Add ID column at the beginning (after the checkbox/bulk actions).
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( 'title' === $key ) {
				$new_columns['arbricks_post_id'] = 'ID';
			}
			$new_columns[ $key ] = $value;
		}
		
		return $new_columns;
	}

	/**
	 * Render the ID column value
	 *
	 * @param string $column_name Current column name.
	 * @param int    $post_id     Current post ID.
	 * @return void
	 */
	public function render_id_column_value( $column_name, $post_id ) {
		if ( 'arbricks_post_id' === $column_name ) {
			echo '<strong>' . esc_html( (string) $post_id ) . '</strong>';
		}
	}

	/**
	 * Render custom admin UI
	 *
	 * @return void
	 */
	public function render_admin_ui(): void {}
}
