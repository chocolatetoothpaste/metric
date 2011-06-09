<?php
$page->template = false;

if( $files = get('js') ):
	$page->content_type = 'text/javascript';
	$dir = PATH_JS;
	$type = 'js';
elseif( $files = get('css') ):
	$page->content_type = 'text/css';
	$dir = PATH_CSS;
	$type = 'css';
endif;

$files = explode( ',', $files );

$cache_file = PATH_CACHE . "/$type";
/*foreach( $page->file as $k => $v )
	$page->file[$k] = "$dir/$v.$type";*/
foreach( $files as &$file )
	include( "$dir/$file.$type" );
unset( $file );

?>
