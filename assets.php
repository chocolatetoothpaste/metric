<?php
$page->template = false;

//	attempt to figure out what is being
//	requested, otherwise 501 "Not Implemented"
if( $f = get( 'js' ) ):
//if( keyAndValue( $get, 'js' ) ):
	$type = 'js';
	$page->content_type = 'text/javascript';
	$dir = PATH_JS;

elseif( $f = get( 'css' ) ):
//elseif( keyAndValue( $get, 'css' ) ):
	$type = 'css';
	$page->content_type = 'text/css';
	$dir = PATH_CSS;

/*elseif( $files = get( 'img' ) ):
	$array = $__files['img'];
	$content_type = $array[$files]['mime'];
	//	images array is copied instead of
	//	referenced since it gets modified
	$array[$files] = $array[$files]['path'];*/

endif;

//	initialize a few vars...
//$files = explode( ',', $get[$type] );
$files = explode( ',', $f );

foreach( $files as $k => $v )
	$time[] = $dir . "/$v.$type";

$time = array_map( 'filemtime', $time );
$time = max( $time );
$cache = ( get( 'cache', 'yes' ) === 'yes' );
$unique_id = get( 'uid', 0 );
$length = 0;
$hash = md5( $f ) . "-{$unique_id}-{$time}";
$cache_file = PATH_CACHE . "/{$type}/{$hash}";

//	check if user has a local cached file, then
//	check for a server cached file, otherwise
//	generate a new file and cache it if possible
header( 'Cache-Control: public, must-revalidate, max-age=0' );
$date = new DateTime('now', new DateTimeZone('UTC'));
$d = $date->format('D, d M Y H:i:s T');
$date = strtotime( '+1 month' );
$date = gmdate( 'D, d M Y H:i:s T', $date );
header( "Expires: $d" );


if( $cache && keyAndValue( $_SERVER, 'HTTP_IF_NONE_MATCH', $hash ) ):
	header( 'HTTP/1.1 304 Not Modified' );
	die;

elseif( $cache && file_exists( $cache_file ) && filesize( $cache_file ) > 0 ):
	header( "Content-Type: {$content_type}" );
	header( "X-Cache-Retrieved: {$hash}" );
	echo file_get_contents( $cache_file );
	die;

elseif( $files ):
	//	start the output buffer to capture the resource being included
	ob_start();

	//	check if each file exists in the array as well as on the file
	//	system to avoid error messages from screwing up the resource
	foreach( $files as $file ):
		if( file_exists( "$dir/$file.$type" ) )
			include( "$dir/$file.$type" );
	endforeach;

	//	write the new file if possible
	if( $cache && !error_get_last() )
	{
		header( "Etag: {$hash}" );
		if( is_writable( $dir ) )
			file_put_contents( $cache_file, ob_get_contents(), LOCK_EX );
	}
	ob_end_flush();
else:
	header( 'HTTP/1.1 204 No Content' );
	die;
endif;
?>
