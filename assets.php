<?php
$page->template = false;

if( $page->request = '/js' ):
	$page->content_type = 'text/javascript';
	$dir = PATH_JS;
	$type = 'js';
elseif( $page->request = '/css' ):
	$page->content_type = 'text/css';
	$dir = PATH_CSS;
	$type = 'css';
endif;

$files = $_GET['load'];
$files = explode( ',', $files )

$cache_file = PATH_CACHE . "/$type";
/*foreach( $page->file as $k => $v )
	$page->file[$k] = "$dir/$v.$type";*/
foreach( $files as &$file )
	include( "$dir/$file.$type" );
unset( $file );

?>
