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

// load the page/view then render it
$page->load();
$page->render();

// end timing page execution and display it as a comment after </html>
if( $page->template )
	echo '<!-- ', microtime( true ) - $_start__, ' hash: ', $page->hash, '-->';
?>