<?php
namespace Metric\Page;

// begin timing page execution
$_start__ = microtime( true );

// the order of the following 2 lines is important, don't change them
include( 'include/include.inc.php' );
session_start();

// make sure the page doesn't get cached unless told to
header( "Cache-Control: must-revalidate, max-age=0" );


// load the page/view then render it
$page = Model::load( strtok( $_SERVER['REQUEST_URI'], '?' ) );
$page->template( $config->template );
$page->render();

// end timing page execution and display it as a comment after </html>
if( $page->template )
	echo '<!-- ', microtime( true ) - $_start__, ' hash: ', $page->hash, '-->';