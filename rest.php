<?php
include( $config->PATH_LIB_INCLUDE . '/http_status.inc.php' );

$page->template = false;
$page->content_type = ( ! empty( $_SERVER['HTTP_ACCEPT'] )
	? $_SERVER['HTTP_ACCEPT']
	: 'application/json' );

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

//$page->authorize($input);
//$service = $page->callback[0];
//$service::authenticate($input);

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
	$page->body = json_encode( $page->response );
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

header( $config->http_status[$page->response['status']] );
header( 'Date: ' . gmdate( DATE_RFC1123 ) );

if(	!empty($config->DEV) )
{
	header( 'X-Execute-Time: ' .  microtime( true ) - $_start__ );
}

$page->render();
?>
