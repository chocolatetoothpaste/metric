<?php
$this->template = false;

// find the type of content to load and set some vars
if( $files = get('js') ):
	$this->content_type = 'text/javascript';
	$dir = $config->PATH_JS;
	$ext = 'js';

elseif( $files = get('css') ):
	$this->content_type = 'text/css';
	$dir = $config->PATH_CSS;
	$ext = 'css';

elseif( $files = get( 'frag' ) ):
	$this->content_type = 'text/html; charset=utf-8';
	$dir = $config->PATH_FRAG;
	$ext = 'phtml';

endif;

$list = explode( ',', $files );
$max = 0;

// array_walk( $list, function( $v ) use( $dir, $ext, &$max ) {
// 	$v = "$dir/$v.$ext";
// 	if( file_exists( $v ) )
// 		$max = max( filemtime( $v ), $max );
// });

//$page->cache( $files, $max );

foreach( $list as &$file )
{
	// check to see if a "meta" reference has been defined in config
	if( ! empty( $config->{$ext} ) && ! empty( $config->{$ext}[$file] ) )
	{
		foreach( $config->{$ext}[$file] as $f )
			require( "$dir/$f.$ext" );
	}

	else
	{
		$file = "$dir/$file.$ext";
		require( $file );
	}
}

//$this->mtime = array_walk( 'filemtime', $list );
//$this->mtime = max( $this->mtime );


// foreach( $list as &$file )
// 	if( $file )
// 		require( $file );
// unset( $file );
?>