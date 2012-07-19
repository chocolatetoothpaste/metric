<?php
/**
 * @author ross paskett <rpaskett@gmail.com>
 */

///
// this passes script errors to the exception handler
set_error_handler( function( $errno, $errstr, $errfile, $errline )
{
	throw new \ErrorException( $errstr, 0, $errno, $errfile, $errline );
});
//*/

date_default_timezone_set( 'America/Denver' );

// these files are required, they include some
// essential constants and some setup functions
$root = dirname( __DIR__ );
require( $root . '/class/config.class.php' );
$config = new config;

require( dirname( $root ) . '/config.inc.php' );
require( 'config.inc.php' );
include( 'functions.inc.php' );

// this will attempt to autoload a class, otherwise
// it throws an exception and halts execution
function __autoload( $file )
{
	global $config;
	$file = trim( $file, '\\' );

	try
	{
		require_once( $config->classes[$file] );
	}
	catch( Exception $e )
	{
		die( "Unable to load class $file" . $e->getMessage() );
	}

}
?>