<?php
$page->template = false;
$page->authorize();
$page->content_type = $_SERVER['HTTP_ACCEPT'];
$page->response = array(
	'success' => 'false',
	'status' => $config->HTTP_UNAUTHORIZED,
	'message' => 'You do not have permission to access the resource at '
		. $page->request );

if( $_SERVER['REQUEST_METHOD'] === 'GET' )
{
	$data =& $_GET;
	$input = $_SERVER['QUERY_STRING'];
}
else
{
	$input = file_get_contents( 'php://input' );
	parse_str( $input, $data );
}

/**
 * @see	\Service\Model::init()
 */
$page->response = call_user_func_array( $page->callback, array(
	'method'	=>	$_SERVER['REQUEST_METHOD'],
	'params'	=>	$page->params,
	'data'		=>	$data
) );

// determine response type and pass response through to the page handler
if( $page->content_type === 'application/json' )
{
	$page->response = json_encode( $response );
}
elseif( $page->content_type === 'application/xml' )
{
}
else
{
	$page->response['status'] = $config->HTTP_NOT_ACCEPTABLE;
	$page->response['message'] = 'The server was unable to understand your request,'
		. ' please check your request parameters.';
}

header( $__http_status[$response['status']] );
header( 'Date: ' . gmdate( DATE_RFC1123 ) );

if(	$config->DEV )
{
	$_finish__ = microtime( true );
	header( 'X-Execute-Time: ' .  $_finish__ - $_start__ );
}

$page->render( $page->response );
?>