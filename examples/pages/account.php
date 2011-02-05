<?php
//require_once( getenv( 'DOCUMENT_ROOT' ) . '/bootstrap.php' );
$page->authenticate( PERM_ACTION_CREATE|PERM_ACTION_UPDATE, 'accounts' );

$action = request('action');

$id = keyAndValue( $page->params, 'account_id' );
$account = new Account( $id );
$account->getMeta();

if( $action === 'create' )
{
	$post = post('form');
	if( keyAndValue( $post, 'password' ) && $post['password'] === keyAndValue( $post, 'conf_password' ) )
		$post['password'] = encrypt_password( $post['password'] );

	$page->request( URL_API_REST . '/account', 'PUT', $post );
}
elseif( isset( $_GET['edit'] ) )
{
	$form_url = URL_SECURE_AREA . '/account/' . $id;
	$action = 'update';
}
elseif( isset( $_GET['add'] ) )
{
	$form_url = URL_SECURE_AREA . '/account';
	$action = 'create';
}

$success = json_decode( $page->response );

if( !empty( $success ) && $success->success == 'true' )
{
	$page->redirect( URL_SECURE_AREA . '/users' );
}
var_dump($page->response);

$page->request( URL_API_REST . "/user/$id" );
$response = json_decode($page->response);
$user =& $response->data;

?>