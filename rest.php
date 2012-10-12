<?php
include( $config->PATH_LIB_INCLUDE . '/http_status.inc.php' );

$this->template = false;

// until another data type is accepted, just default to this...
$this->content_type = 'application/json';

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
		'success'	=> 'false',
		'status'	=> $config->HTTP_INTERNAL_SERVER_ERROR,
		'message'	=> 'Serice not available'
	);

	$error = $this->callback[0] . ' not found';

	if( file_exists( $config->PATH_SERVICE[$this->callback[0]] ) )
	{
		$end = get_declared_classes();
		$end = end( $end );
		$debug = 'File exists but service could not be loaded. Found ' . $end;
		unset( $end );
	}

	if( $config->DEV )
	{
		$response['error'] =& $error;
		$response['debug'] =& $debug;
	}
	else
	{
		error_log( $error );
		error_log( $debug );
	}
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
else
{
	header( $config->http_status[$config->HTTP_NOT_ACCEPTABLE] );
}

?>