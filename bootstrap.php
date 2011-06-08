<?php
/**
 * @author ross paskett <rpaskett@gmail.com>
 * @see /page/controller and /page/view for actual pages
 */

// starting a timer to time how long it takes to process a page request.
$_start__ = microtime( true );

include( 'include/include.inc.php' );
session_start();

$page = new page();
$page->uid = get( 'uid', '0' );
$page->parseURL( getenv( 'REQUEST_URI' ) );
$page->template = PAGE_TEMPLATE;

// grab the most recent mtime of a file/files, create a hash
$page->mtime();
$hash = md5( $page->request ) . "-{$page->uid}-{$page->mtime}";

//$visibility = 'public';
//header( "Cache-Control: $visibility, must-revalidate, max-age=0" );
header( "Cache-Control: public, must-revalidate, max-age=0" );

// check if user has a local cached file
// else check for a server cached file
// else generate a new file and if possible cache it
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

	/*//grab all declared class names to compare after including file
	$declared_classes = get_declared_classes();
	//*/
	require( $page->file );
	if( $page->view )
		require( $page->view );
	
	/*// grab the new list of classes and see
	// if there was one defined in $page->file
	$new_class = array_diff( get_declared_classes(), $declared_classes );

	// if a new class was found, instantiate it and call init function
	if( $new_class )
	{
	list( $new_class ) = array_values( $new_class );
		$class = new $new_class;
		$class->init();
	}
	//*/

	$body = ob_get_clean();
	header( "Content-Type: {$page->content_type}" );
	$page->render( $body );

	// cache the page if the stars are aligned (no errors),
	// because caching an errored page would be stupid
	if( strlen( $page->body ) && $page->cache && !error_get_last() )
	{
		$date = strtotime( '+1 month' );
		$date = gmdate( DATE_RFC1123, $date );
		header( "Etag: {$hash}" );
		header( "Expires: {$date}" );

		if( is_writable( PATH_CACHE . '/pages' ) )
			file_put_contents( $cache_file, ob_get_contents(), LOCK_EX );
	}

	// the page is displayed whether it's cached or not, so flush the buffer
	ob_end_flush();

	// stop the timer and calculate execution
	// time, displays as comment after </html>
	$_finish__ = microtime( true );
	if( $page->template )
		printf( '<!-- %f hash: %s-->', ( $_finish__ - $_start__ ), $hash );
}
?>
