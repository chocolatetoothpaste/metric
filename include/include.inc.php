<?php
/**
 * @author ross paskett <rpaskett@gmail.com>
 */

/*function exception_error_handler( $errno, $errstr, $errfile, $errline ) {
    throw new ErrorException ($errstr, 0, $errno, $errfile, $errline );
}*/

// this passes script errors to the exception handler
set_error_handler(function( $errno, $errstr, $errfile, $errline )
{
	throw new ErrorException( $errstr, 0, $errno, $errfile, $errline );
});

date_default_timezone_set( 'America/Denver' );

class config{}
$config = new config;


// these files are required, they include some
// essential constants and some setup functions
require( '../config.inc.php' );
require( 'config.inc.php' );
include( 'http_status.inc.php' );
include( 'functions.inc.php' );


// this will attempt to autoload a class, otherwise
// it throws an exception and halts execution
function __autoload( $file )
{
	$part = explode( '\\', $file );
	global $config;
	$sub = ( empty( $part[1] ) ? 'class' : $part[0] );
	$file = trim( $file, '\\' );
	try
	{
		if( !is_file( $config->classes[$file] ) )
			throw new Exception( "Unable to load class: $file" );

		require_once( $config->classes[$file] );
	}
	catch( Exception $e )
	{
		die( $e->getMessage() );
	}
}
?>
