<?php

/**
 * The main config file.  Sets up a lot of vars and settings.
 * @author ross paskett <rpaskett@gmail.com>
 */

// a couple vars used during development
define( 'DEV', ( false !== strpos( getenv( 'SERVER_NAME' ), 'cal.dev' ) ) );

// email address for administrator
define( 'ADMIN_EMAIL', 'rpaskett@gmail.com' );

// a couple setup vars
define( 'DIE_BEFORE_REDIRECT', false );
define( 'FORCE_SSL', false );

/*//
 * Remove the star in the above line to enable constants pertaining to user/auth system

define( 'PASSWORD_LENGTH', 7 );

// user types
define( 'USER_ADMIN', 1 );
define( 'USER_ACCOUNT_MANAGER', 2 );
define( 'USER_USER', 4 );
define( 'USER_ALL', USER_ADMIN|USER_ACCOUNT_MANAGER|USER_USER );

// permission types
define( 'PERM_ACTION_READ', 1 );
define( 'PERM_ACTION_CREATE', 2 );
define( 'PERM_ACTION_UPDATE', 4 );
define( 'PERM_ACTION_DELETE', 8 );

//	accounts statuses
define( 'ACCT_STATUS_ACTIVE', 800 );
define( 'ACCT_STATUS_LIMITED', 804 );
define( 'ACCT_STATUS_EXPIRED', 809 );
define( 'ACCT_STATUS_INACTIVE', 811 );

$__account = array(
	'status'	=>	array(
		ACCT_STATUS_ACTIVE		=>	'Active',
		ACCT_STATUS_LIMITED		=>	'Restricted',
		ACCT_STATUS_EXPIRED		=>	'Expired',
		ACCT_STATUS_INACTIVE	=>	'Inactive'
	),
	'max_users'	=>	array(
		'Unlimited', '1', '5', '25', '100'
	)
);

//*/

/*//
 * DB related vars

$__db_connections = array(
	'test_db' => array(
		'driver'	=>	'mysql',
		'username'	=>	'test_user',
		'password'	=>	'test_pass',
		'host'		=>	'localhost',
		'dbname'	=>	'test_admin'
	)
);

define( 'DB_MAIN_READ', ( DEV ? 'local_admin' : 'live_read' ) );
define( 'DB_MAIN_WRITE', ( DEV ? 'local_admin' : 'live_write' ) );
define( 'DB_MAIN', ( DEV ? 'local_admin' : 'live_admin' ) );

//*/

// static paths
define( 'PATH_ROOT',		dirname( $_SERVER['DOCUMENT_ROOT'] ) );
define( 'PATH_HTDOCS',		PATH_ROOT . '/htdocs' );
define( 'PATH_PAGE',		PATH_ROOT . '/pages' );
define( 'PATH_JS',			PATH_HTDOCS . '/js' );
define( 'PATH_CSS',			PATH_HTDOCS . '/css' );
define( 'PATH_CACHE',		PATH_ROOT . '/cache' );
define( 'PATH_TEMPLATE',	PATH_ROOT . '/templates' );
define( 'PATH_TAG',			PATH_TEMPLATE . '/tags' );
define( 'PATH_VIEW',		PATH_TEMPLATE . '/views' );

define( 'PATH_LIB',			PATH_ROOT . '/lib' );
define( 'PATH_INCLUDE',		PATH_LIB . '/include' );
define( 'PATH_CLASS',		PATH_LIB . '/classes' );
define( 'PATH_SERVICE',		PATH_ROOT . '/services' );
define( 'PATH_DOMAIN',		PATH_ROOT . '/domains' );

// some universal pages
define( 'PAGE_INDEX',	PATH_PAGE . '/index.php' );
define( 'PAGE_404',		PATH_PAGE . '/404.php' );
define(	'PAGE_TEMPLATE', 'sample.template.php' );
define(	'PAGE_PERMISSION_DENIED', PATH_PAGE . '/permission_denied.php' );

// some configurable URLs
define( 'URL_API', 'http://api.sample.com' );
define( 'URL_REST', '/rest' );
define( 'URL_API_REST', URL_API . '/rest' );
define( 'URL_SECURE_AREA', '/admin' );
define( 'URL_GROUP_ALIAS', '/group' );


$__urls = array(
	'/'												=>	PATH_PAGE . '/index.php',
	/*//
	 * Some sample URLs

	'/logout'										=>	PATH_PAGE . '/login.php',
	URL_REST		. '/users'						=>	array('UserService','collection'),
	URL_REST		. '/user/%id'					=>	array('UserService','init'),
	URL_REST		. '/account/%id'				=>	array('AccountService','init'),
	// this MUST be after ALL valid REST URLs
	URL_REST		. '/*'							=>	PATH_PAGE . '/rest.php',
	URL_SECURE_AREA									=>	PATH_PAGE . URL_SECURE_AREA . '/index.php',
	URL_SECURE_AREA . '/account/%account_id'		=>	PATH_PAGE . URL_SECURE_AREA . '/account.php',
	URL_SECURE_AREA	. '/user/%user_id'				=>	PATH_PAGE . URL_SECURE_AREA . '/user.php'

	//*/
);

$__files = array(
	// class files
	'classes' => array(
		'message'				=>	PATH_CLASS . '/message.class.php',
		'timer'					=>	PATH_CLASS . '/timer.class.php',
		'mysql'					=>	PATH_CLASS . '/mysql.class.php',
		'query'					=>	PATH_CLASS . '/query.class.php',
		'page'					=>	PATH_CLASS . '/page.class.php',
		'pmail'					=>	PATH_CLASS . '/pmail.class.php',
		'database'				=>	PATH_CLASS . '/database.abstract.php',
		'DomainModel'			=>	PATH_CLASS . '/model.domain.php',

		/*//
		 * Sample files

		'User'					=>	PATH_DOMAIN . '/user.domain.php',
		'Group'					=>	PATH_DOMAIN . '/group.domain.php',
		'Meta'					=>	PATH_DOMAIN . '/meta.domain.php',
		'Email'					=>	PATH_DOMAIN . '/email.domain.php',
		'Phone'					=>	PATH_DOMAIN . '/phone.domain.php',
		'Address'				=>	PATH_DOMAIN . '/address.domain.php',
		'Account'				=>	PATH_DOMAIN . '/account.domain.php',
		'Permission'			=>	PATH_DOMAIN . '/permission.domain.php',
		'ServiceModel'			=>	PATH_SERVICE . '/model.service.php',
		'UserService'			=>	PATH_SERVICE . '/user.service.php',
		'AccountService'		=>	PATH_SERVICE . '/account.service.php',
		//*/
	)
);

?>
