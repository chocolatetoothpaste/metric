<?php
/**
 * @author ross paskett <rpaskett@gmail.com>
 * @see /page/controller and /page/view for actual pages
 */
namespace Metric;

// begin timing page execution
$_start__ = microtime( true );

include( 'include/include.inc.php' );
session_start();

$page = new $config->template();
$page->parseURL( strtok( $_SERVER['REQUEST_URI'], '?' ) );

// make sure the page doesn't get cached unless told to
header( "Cache-Control: must-revalidate, max-age=0" );

// start an output buffer to begin building page. this allows headers to be set
// in the script before anything is output
ob_start();

// it is likely page controllers will be classes in the future, so this section
// and the section below are left as comments to enable it
/*// get all declared class names to compare after including file
$classes = get_declared_classes();
//*/

// grab the page and view (if there is one)
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

// end timing page execution and display it as a comment after </html>
if( $page->template )
	printf( '<!-- %f hash: %s-->', ( microtime( true ) - $_start__ ), $page->hash );

?>
