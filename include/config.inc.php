<?php

define( 'PATH_LIB_CLASS',		PATH_LIB . '/class' );
define( 'PATH_LIB_INCLUDE',		PATH_LIB . '/include' );

config::$classes = array_merge( array(
		'message'				=>	PATH_LIB_CLASS . '/message.class.php',
		'timer'					=>	PATH_LIB_CLASS . '/timer.class.php',
		'mysql'					=>	PATH_LIB_CLASS . '/mysql.class.php',
		'query'					=>	PATH_LIB_CLASS . '/query.class.php',
		'page'					=>	PATH_LIB_CLASS . '/page.class.php',
		'pmail'					=>	PATH_LIB_CLASS . '/pmail.class.php',
		'database'				=>	PATH_LIB_CLASS . '/database.abstract.php',
		'Domain\Model'			=>	PATH_LIB_CLASS . '/model.domain.php',
		'Domain\Meta'			=>	PATH_LIB_CLASS . '/meta.domain.php',
		'Service\Model'			=>	PATH_LIB_CLASS . '/model.service.php',
), config::$classes );

?>
