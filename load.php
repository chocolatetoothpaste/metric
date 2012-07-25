<?php
$this->template = false;

// find the type of content to load and set some vars
if( $files = get('js') ):
	$this->content_type = 'text/javascript';
	$dir = $config->PATH_JS;
	$type = 'js';
elseif( $files = get('css') ):
	$this->content_type = 'text/css';
	$dir = $config->PATH_CSS;
	$type = 'css';
endif;

//$page->cache( $files );
$files = explode( ',', $files );

foreach( $files as &$file )
	$file = "$dir/$file.$type";

//$this->mtime = array_walk( 'filemtime', $files );
//$this->mtime = max( $this->mtime );


foreach( $files as &$file )
	require( $file );
unset( $file );
?>