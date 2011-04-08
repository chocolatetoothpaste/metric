<?php

$page->template = false;
$format = get('format', 'json');
$response = array( 'success' => 'false', 'status' => HTTP_BAD_REQUEST );

//$signature = $_SERVER['REQUEST_METHOD'] "\n\n" . $page->headers['Date'] . file_get_contents('php://input');
//hash_hmac('md5', utf8_encode("DELETE\n\nToday/user/1"), 'beetle67');

/*if( !empty( $page->headers['Token'] ) && $page->headers['Token'] != $token )
{
	header('HTTP/1.0 401 Unauthorized');
	die;
}*/

$input = file_get_contents( 'php://input' );

if( $_SERVER['REQUEST_METHOD'] === 'GET' )
	$args = $_GET;
else
	parse_str( $input, $args );

$page->params = $page->params + array( 'method' => $_SERVER['REQUEST_METHOD'], 'data' => $args );

if( is_callable( $page->callback ) )
{
	$response = call_user_func_array( $page->callback, $page->params );
}

// if not in dev environment, clean the output buffer
// so any errors don't screw up the server response
if( !DEV )
	ob_clean();

if( $format === 'json' )
{
	header($__http_status[$response['status']]);
	header('Date: ' . gmdate('r'));
	$page->content_type = 'application/json';
	if(	DEV )
	{
		$_finish__ = microtime(true);
		$response['time'] = ( $_finish__ - $_start__ );
	}
	echo json_encode( $response );
}

?>
