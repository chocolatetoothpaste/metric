<?php
$page->template = false;
if( $files = get('js') ):
	$page->content_type = 'text/javascript';
	$dir = $config->PATH_JS;
	$type = 'js';
elseif( $files = get('css') ):
	$page->content_type = 'text/css';
	$dir = $config->PATH_CSS;
	$type = 'css';
endif;

$files = explode( ',', $files );
foreach( $files as &$file )
	$file = "$dir/$file.$type";
unset( $file );
$page->mtime = array_map( 'filemtime', $files );
$page->mtime = max( $page->mtime );
//$page->cache( $files );

foreach( $files as &$file )
	require( $file );
unset( $file );
?>
