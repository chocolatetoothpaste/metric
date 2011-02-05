<?php
//require_once( getenv( 'DOCUMENT_ROOT' ) . '/bootstrap.php' );

if( !https() )
{
	$page->redirect( 'https://' . server('SERVER_NAME') . '/login' );
}

if( post('action') === 'login' )
{
	$password = encrypt_password( post('password') );
	$criteria = array( 'username' => post('username'), 'password' => $password );
	$user = new User( $criteria );
	if( $user->authenticate() )
	{
		$user->login();
		$_SESSION['user'] = $user;
		$page->redirect('/admin/index');
	}
}
elseif( $page->request === '/logout' )
{
	$page->template = false;
	$_SESSION = array();
	session_destroy();
	$page->redirect('/login');
}

// this must be at the end, otherwise you can never log out :$
if( keyAndValue( $_SESSION, 'user' ) instanceof User && $_SESSION['user']->authenticate() )
	$page->redirect( '/admin/index' );

$page->title = 'Log in';
?>