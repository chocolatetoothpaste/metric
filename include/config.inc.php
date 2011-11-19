<?php

/**
 * For setting up bitwise values in apps
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

$config->classes = array_merge( array(
	'message'		=>	$config->PATH_LIB_CLASS . '/message.class.php',
	'timer'			=>	$config->PATH_LIB_CLASS . '/timer.class.php',
	'mysql'			=>	$config->PATH_LIB_CLASS . '/mysql.class.php',
	'query'			=>	$config->PATH_LIB_CLASS . '/query.class.php',
	'page'			=>	$config->PATH_LIB_CLASS . '/page.class.php',
	'request'		=>	$config->PATH_LIB_CLASS . '/request.class.php',
	'pmail'			=>	$config->PATH_LIB_CLASS . '/pmail.class.php',
	'database'		=>	$config->PATH_LIB_CLASS . '/database.abstract.php',
	'Domain\Model'	=>	$config->PATH_LIB_CLASS . '/model.domain.php',
	'Domain\Meta'	=>	$config->PATH_LIB_CLASS . '/meta.domain.php',
	'Service\Model'	=>	$config->PATH_LIB_CLASS . '/model.service.php',
), $config->classes );
?>
