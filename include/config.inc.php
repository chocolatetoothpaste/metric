<?php

/*
 * Bitwise values
define('', 1);
define('', 2);
define('', 4);
define('', 8);
define('', 16);
define('', 32);
define('', 64);
define('', 128);
define('', 256);
define('', 512);
define('', 1024);
define('', 2048);
define('', 4096);
define('', 8192);
define('', 16384);
define('', 32768);
define('', 65536);
define('', 131072);
define('', 262144);
define('', 524288);
define('', 1048576);
define('', 2097152);
define('', 4194304);
define('', 8388608);
define('', 16777216);
define('', 33554432);
define('', 67108864);
define('', 134217728);
define('', 268435456);
define('', 536870912);
define('', 1073741824);
*/

// core paths
$config->define( 'PATH_LIB_CLASS',		$config->PATH_LIB . '/class' );
$config->define( 'PATH_LIB_INCLUDE',	$config->PATH_LIB . '/include' );

if( ! isset( $config->PAGE_REST_SERVER ) )
	$config->define( 'PAGE_REST_SERVER', $config->PATH_LIB . '/rest.php' );

if( ! isset( $config->PAGE_404 ) )
	$config->define( 'PAGE_404', $config->PATH_CONTROLLER . '/404.php' );

if( ! isset( $config->DIE_BEFORE_REDIRECT ) )
	$config->define( 'DIE_BEFORE_REDIRECT', false );

if( ! isset( $config->FORCE_SSL ) )
	$config->define( 'FORCE_SSL', false );

$config->alias = array_merge( array(
	'/load'	=>	$config->PATH_LIB . '/load.php'
), $config->alias );

$lib = $config->PATH_LIB_CLASS;
$config->classes = array_merge( array(
	'message'		=>	$lib . '/message.class.php',
	'timer'			=>	$lib . '/timer.class.php',
	'mysql'			=>	$lib . '/mysql.class.php',
	'query'			=>	$lib . '/query.class.php',
	'Metric\Page'	=>	$lib . '/page.abstract.php',
	'request'		=>	$lib . '/request.class.php',
	'pmail'			=>	$lib . '/pmail.class.php',
	'database'		=>	$lib . '/database.abstract.php',
	'Domain\Model'	=>	$lib . '/model.domain.php',
	'Domain\Meta'	=>	$lib . '/meta.domain.php',
	'Service\Model'	=>	$lib . '/model.service.php',
	'Service\Collection'	=>	$lib . '/collection.service.php',
	'Service\RESTException'	=>	$lib . '/rest.exception.php'
), $config->classes );
?>