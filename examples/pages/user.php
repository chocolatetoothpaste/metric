<?php
//require_once( getenv( 'DOCUMENT_ROOT' ) . '/bootstrap.php' );
$page->authenticate( PERM_ACTION_CREATE|PERM_ACTION_UPDATE, 'users' );
$action = request('action');
$id = keyAndValue( $page->params, 'user_id' );

if( $action === 'update' )
{
	$post = post('form');
	if( keyAndValue( $post, 'password' ) && $post['password'] === keyAndValue( $post, 'conf_password' ) )
		$post['password'] = encrypt_password( $post['password'] );

	$page->request( URL_API_REST . "/user/$id", 'POST', $post );
}
elseif( $action === 'create' )
{
	$post = post('form');
	if( keyAndValue( $post, 'password' ) && $post['password'] === keyAndValue( $post, 'conf_password' ) )
		$post['password'] = encrypt_password( $post['password'] );

	$page->request( URL_API_REST . '/user', 'PUT', $post );
}
elseif( isset( $_GET['delete'] ) )
{
	//echo 'delete';
	$page->request( URL_API_REST . "/user/$id", 'DELETE' );
	//die;
}
elseif( isset( $_GET['add'] ) )
{
	$form_url = URL_SECURE_AREA . '/user';
	$submit = 'add user';
	$action = 'create';
}
elseif( isset( $_GET['edit'] ) )
{
	$form_url = URL_SECURE_AREA . "/user/$id";
	$submit = 'update user';
	$action = 'update';
}
else
{
}

$success = json_decode( $page->response );

if( !empty( $success ) && $success->success == 'true' )
{
	$page->redirect( URL_SECURE_AREA . '/users' );
}

$page->request( URL_API_REST . "/user/$id" );
$response = json_decode($page->response);
$user =& $response->data;

/*if( $response->success == 'false' )
{
	die('user not found');
}*/

/*//
var_dump($user);
die;
//*/


?>