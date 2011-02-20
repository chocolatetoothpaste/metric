<?php
/**
 * This file is just a dispatcher.  It handles URL processing
 * and page rendering. See /pages for the actual page files. This
 * bootstrapper also does inline page control, it's sexy. Just add
 * this line to the top of the pages you want to process:
 *
 * require_once( getenv( 'DOCUMENT_ROOT' ) . '/bootstrap.php' );
 *
 * @author ross paskett <rpaskett@gmail.com>
 */

// starting a timer to time how long it takes to process a page request.
// using this instead of timer class to avoid interference with benchmarking
// that will occur on other pages. also, this way, no time is lost waiting for
// the timer class to be loaded while other things may happen in the meantime
$__start__ = microtime(true);

include( 'include/include.inc.php' );
session_start();

$page = new page();
$page->template = PAGE_TEMPLATE;
$page->uid = get( 'uid', '0' );
$page->parseURL( getenv( 'REQUEST_URI' ) );
$page->mtime();

$hash = md5( $page->request ) . "-{$page->uid}-{$page->mtime}";
$cache_file = PATH_CACHE . "/pages/{$hash}";

$visibility = 'public';
header( "Cache-Control: $visibility, must-revalidate, max-age=0" );

// check if user has a local cached file
// else check for a server cached file
// else generate a new file and cache it if possible
if( keyAndValue( $_SERVER, 'HTTP_IF_NONE_MATCH', $hash ) )
{
	header( $__http_status[HTTP_NOT_MODIFIED] );
	die;
}
elseif( file_exists( $cache_file ) && filesize( $cache_file ) > 0 )
{
	header( "X-Cache-Retrieved: {$hash}" );
	echo file_get_contents( $cache_file );
	die;
}
else
{
	ob_start();
	
	///
	require( $page->file );
	/*/

	//grab all declared class names to compare after including file
	$declared_classes = get_declared_classes();
	require( $page->file );

	// grab the new list of classes and see if there was one defined in $page->file
	$new_class = array_diff( get_declared_classes(), $declared_classes );

	// if a new class was found, instantiate it and call init function
	if( $new_class )
	{
		list( $new_class ) = array_values( $new_class );
		$class = new $new_class;
		$class->init();
	}
	//*/

	// grab buffer contents and clear it to prepare to render
	if( $page->view )
		require( $page->view );

	$body = ob_get_clean();
	header( "Content-Type: {$page->content_type}" );
	$page->render( $body );

	// cache the page if the stars are aligned (no errors)
	if( strlen( $page->body ) && $page->cache && !error_get_last() )
	{
		// this header !!MUST!! be left here otherwise browsers will cache
		// pages that contain errors or otherwise shouldn't be cached
		$date = strtotime( '+1 month' );
		$date = gmdate( 'D, d M Y H:i:s T', $date );
		header( "Etag: {$hash}" );
		header( "Expires: {$date}" );

		if( is_writable( PATH_CACHE . '/pages' ) )
			file_put_contents( $cache_file, ob_get_contents(), LOCK_EX );
	}

	// the page is displayed whether it's cached or not, so flush the buffer
	ob_end_flush();

	// stop the timer and calculate execution time, displays as comment after </html>
	$__finish__ = microtime(true);
	if( $page->template )
		printf( '<!-- %f hash: %s-->', ( $__finish__ - $__start__ ), $hash );
}
?>
