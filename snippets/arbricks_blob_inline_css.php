<?php if(!defined('ABSPATH')) { die(); }  

add_action('wp_head', function() {

        
	// Condition Builder helper class
	$wpContext = new \WFPCore\WordPressContext();

	// Condition Builder generated Conditions
	if( !( ( $wpContext->is_everywhere() ) )) {
		return false;
	}
        ?>
        <style wpcb-ids='25' class='wpcb2-inline-style'>

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
	width: 50%;
	height: 50%;
	overflow: hidden;
	transform: scale(1);
	position: absolute;
  inset: 0;
}

.blob::before {
	position: absolute;
	border-radius: 99999px;
	width: 100%;
	height: 100%;
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
*:has(>.blob) {
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
*:has(>.blob) > *:not(.blob) {
    z-index: 1;
    position: relative; /* Ensure the z-index takes effect */
}
        </style>

    <?php
    }, 10);

