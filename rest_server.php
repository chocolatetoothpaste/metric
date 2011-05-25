<?php

$page->template = false;
$page->content_type = $_SERVER['HTTP_ACCEPT'];
$response = array( 'success' => 'false', 'status' => HTTP_BAD_REQUEST );
/*
$user_name = apache_request_headers();
$user_name = $user_name['Authorization'];
$user_name = explode( ':', $user_name);

$db = mysql::instance($config->db[DB_MAIN]);
$query = 'SELECT api_key FROM users WHERE active = 1 AND username = :username';
$db->execute( $query, array( 'username' => $user_name[0] ) );
$key = $db->result->fetchColumn();
$key = 'asdf';
*/
if( $_SERVER['REQUEST_METHOD'] === 'GET' )
	$input = $data = $_GET;
else
{
	$input = file_get_contents( 'php://input' );
	parse_str( $input, $data );
}
/*
$contents = $_SERVER['REQUEST_METHOD'] . ':' . $input;
$hash = hash_hmac( 'sha1', utf8_encode($contents), $key );

echo $hash;

if( $hash !== $user_name[1] )
{
	header($__http_status[HTTP_UNAUTHORIZRD]);
	die;
}
*/

$page->params = $page->params + array(
	'method' => $_SERVER['REQUEST_METHOD'],
	'data' => $data
);

if( is_callable( $page->callback ) )
{
	$response = call_user_func_array( $page->callback, $page->params );
}

// if not in dev environment, clean the output buffer
// so any errors don't screw up the server response
if( !DEV )
	ob_clean();

// determine reponse format and set up response
if( $page->content_type === 'application/json' )
{
	$page->body = json_encode( $response );
}
elseif( $page->content_type === 'application/xml' )
{
	// handle xml
}
else
{
	$response['status'] = HTTP_NOT_ACCEPTABLE;
	$page->body = 'Invalid format';
}

header($__http_status[$response['status']]);
header('Date: ' . gmdate('r'));

if(	DEV )
{
	$_finish__ = microtime(true);
	header('X-Execute-Time: ' .  $_finish__ - $_start__ );
}

$page->render( $page->body );
?>
