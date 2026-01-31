<?php
/**
 * Blob Effect CSS Snippet
 *
 * @package ArBricks
 * @since 2.0.0
 */

namespace ArBricks\Snippets\Built_In;

use ArBricks\Snippets\Abstract_Snippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Blob_Css
 */
class Blob_Css extends Abstract_Snippet {

	/**
	 * Get snippet ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'blob_css';
	}

	/**
	 * Get snippet label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Blob Effect CSS', 'arbricks' );
	}

	/**
	 * Get snippet description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Animated blob background effect.', 'arbricks' );
	}

	/**
	 * Get snippet category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'styles';
	}

	/**
	 * Apply snippet functionality
	 *
	 * @return void
	 */
	public function apply() {
		if ( ! $this->check_context_condition() ) {
			return;
		}

		$css = '
/* ArBricks Blob */
.blob {
  --blob-color-1: var(--primary-90);
  --blob-color-2: var(--primary-70);
  --blob-color-3: var(--primary-50);
  --blob-color-4: var(--primary-30);
  --blob-color-5: var(--primary-10);
  --blob-color-6: var(--primary);
	filter: blur(100px);
	margin: auto;
	inline-size: 50%;
	block-size: 50%;
	overflow: hidden;
	transform: scale(1);
	position: absolute;
  inset: 0;
}

.blob::before {
	position: absolute;
	border-radius: 99999px;
	inline-size: 100%;
	block-size: 100%;
	inset: 0;
	margin: auto;
	background: conic-gradient(
		from 0deg,
		var(--blob-color-1),
		var(--blob-color-2),
		var(--blob-color-3),
		var(--blob-color-4),
		var(--blob-color-5),
		var(--blob-color-6)
	);
	animation: spinBlob 10s linear infinite;
	content: "";
  z-index: -1;
}

@keyframes spinBlob {
	0% {
		transform: rotate(0deg) scale(2);
	}

	100% {
		transform: rotate(1turn) scale(2);
	}
}

/* Select any element that has a child with the class .blob */
/* Ensure the parent of .blob is positioned */
*:has(> .blob) {
    position: relative;
}
/* Set a lower z-index for .blob to act as the background */
.blob {
    z-index: -1;
}
/* Ensure all siblings of .blob (before and after) have a higher z-index */
.blob ~ *, 
.blob + * {
    z-index: 1;
    position: relative; /* Ensure the z-index takes effect */
}
/* Ensure siblings that come before .blob also have a higher z-index */
*:has(> .blob) > *:not(.blob) {
    z-index: 1;
    position: relative; /* Ensure the z-index takes effect */
}
';

		$this->add_inline_css( $css, 'blob' );
	}
}
