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
//$visibility = 'public';
//header( "Cache-Control: $visibility, must-revalidate, max-age=0" );
header( "Cache-Control: public, must-revalidate, max-age=0" );

ob_start();

// grab the page and view (if there is one)

/*// get all declared class names to compare after including file
$classes = get_declared_classes();
//*/
require_once( $page->file );
if( $page->view )
	require_once( $page->view );

/*// get the new list of classes and see if a new one was defined by controller
$new_class = array_diff( get_declared_classes(), $classes );
unset( $classes );
// if a new class was found, instantiate it and call init function
if( $new_class )
{
	list( $new_class ) = $new_class;
	$class = new $new_class;
	$class->init();
}
//*/

$page->body = ob_get_clean();
$page->render();

//while( @ob_end_flush() );

// stop the timer and calculate execution
// time, displays as comment after </html>
$_finish__ = microtime( true );
if( $page->template )
	printf( '<!-- %f hash: %s-->', ( $_finish__ - $_start__ ), $page->hash );

?>
