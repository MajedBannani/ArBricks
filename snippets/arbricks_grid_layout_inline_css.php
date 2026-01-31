<?php if(!defined('ABSPATH')) { die(); }  

add_action('wp_head', function() {

        
	// Condition Builder helper class
	$wpContext = new \WFPCore\WordPressContext();

	// Condition Builder generated Conditions
	if( !( ( $wpContext->is_everywhere() ) )) {
		return false;
	}
        ?>
        <style wpcb-ids='24' class='wpcb2-inline-style'>

        
/* ArBricks Grid Layout */ 
:root {
  --content-max-width: var(--max-screen-width);
  --section-inline-padding: var(--space-m);
  --ar-grid-layout: [full-start] 1fr 
    [content-start first-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [first-end second-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [second-end content-end] 1fr
    [full-end];
}

.ar-grid-layout {
  --content-max-width: var(--max-screen-width);
  --section-inline-padding: var(--space-m);
  display: grid;
  grid-template-columns: 
    [full-start] 1fr 
    [content-start first-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [first-end second-start] minmax(0, calc(min(var(--content-max-width) / 2, (100vw - var(--section-inline-padding)) / 2))) 
    [second-end content-end] 1fr
    [full-end];
}
.ar-grid-layout > {
  grid-column: content;
}

        </style>

    <?php
    }, 10);

