<?php
include( $config->PATH_LIB_INCLUDE . '/http_status.inc.php' );

$this->template = false;
$this->content_type = ( ! empty( $_SERVER['HTTP_ACCEPT'] )
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

//$this->authorize($input);
//$service = $this->callback[0];
//$service::authenticate($input);

/**
 * @see	\Service\Model::init()
 */
if( is_callable( $this->callback ) )
{
	$response = call_user_func_array( $this->callback, array(
		'method'	=>	$_SERVER['REQUEST_METHOD'],
		'params'	=>	$this->params,
		'data'		=>	$data
	) );
}
else
{
	$response = array(
		'success' => 'false',
		'status' => $config->HTTP_INTERNAL_SERVER_ERROR,
		'error' => 'Serice interface '
			. ( $config->DEV ? $this->callback[0] : '' ) . ' not callable' );
}

// send the http status and the date. content type gets sent by page::render
header( $config->http_status[$response['status']] );
header( 'Date: ' . gmdate( DATE_RFC1123 ) );

// determine response type and pass response through to the page handler
if( $this->content_type === 'application/json' )
	echo json_encode( $response );

elseif( $this->content_type === 'application/xml' )
{
	// build xml response
}
elseif( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' )
	ob_clean();
else
{
	header( $config->http_status[$config->HTTP_NOT_ACCEPTABLE] );
	echo 'The server was unable to understand your request,',
		' please check your request parameters.';
}
?>