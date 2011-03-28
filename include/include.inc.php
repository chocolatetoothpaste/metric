<?php
/**
 * the main include file.  anything that should
 * be included or set up should be done here or in config.inc.php
 * @author ross paskett <rpaskett@gmail.com>
 */

$_SESSION = array();

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler('exception_error_handler');

class config
{
	public static $classes = array();
	public static $urls = array();
	public static $db = array();
}

// these files are required, they include some
// essential constants and some setup functions
require( dirname( $_SERVER['DOCUMENT_ROOT'] . '../' ) . '/config.inc.php' );
require( 'config.inc.php' );
include( 'http_status.inc.php' );
include( 'functions.inc.php' );

// this function must be before session starting. sometimes objects get passed
// as session vars, so the system needs to know how to create them before the
// session tries to create an instance (it does as soon as the session loads)

function __autoload( $file )
{
	if( is_file( config::$classes[$file] ) )
		require_once( config::$classes[$file] );
	else
		throw new Exception( "Unable to load class: $file" );

}
?>
