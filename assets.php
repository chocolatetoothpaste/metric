<?php
if( $page->request = get( 'js' ) ):
	$page->content_type = 'text/javascript';
	$dir = PATH_JS;
	$type = 'js';
elseif( $page->request = get( 'css' ) ):
	$page->content_type = 'text/css';
	$dir = PATH_CSS;
	$type = 'css';
endif;
?>
