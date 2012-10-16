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

$res = explode( ',', $files );
$max = 0;

// array_walk( $res, function( $v ) use( $dir, $type, &$max ) {
// 	$v = "$dir/$v.$type";
// 	if( file_exists( $v ) )
// 		$max = max( filemtime( $v ), $max );
// });

//$page->cache( $files, $max );

foreach( $res as &$file )
{
	if( ! empty( $config->$type[$file] ) )
	{
		if( is_array( $config->$type[$file] ) )
		{
			foreach( $config->$type[$file] as $f )
				require( "$dir/$f.$type" );
		}

		else
		{
			$file = "$dir/$file.$type";
			require( $file );
		}
	}

	else
	{
		$file = "$dir/$file.$type";
		require( $file );
	}
}

//$this->mtime = array_walk( 'filemtime', $res );
//$this->mtime = max( $this->mtime );


// foreach( $res as &$file )
// 	if( $file )
// 		require( $file );
// unset( $file );
?>