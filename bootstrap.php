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
/*//grab all declared class names to compare after including file
$declared_classes = get_declared_classes();
//*/
ob_start();
// grab the page and, if there is one, the view
require_once( $page->file );
if( $page->view )
	require_once( $page->view );

$page->body = ob_get_clean();
$page->render();

//while( @ob_end_flush() );

// stop the timer and calculate execution
// time, displays as comment after </html>
$_finish__ = microtime( true );
if( $page->template )
	printf( '<!-- %f hash: %s-->', ( $_finish__ - $_start__ ), $page->hash );

?>
