<?php
$page->template = false;
$page->content_type = $_SERVER['HTTP_ACCEPT'];
$response = array( 'success' => 'false', 'status' => HTTP_UNAUTHORIZED );

// Authorization header is hidden from PHPs $_SERVER super global, so grab it from apache
$auth = apache_request_headers();

// Make sure credentials were passed, otherwise there's no point in going any further
if( empty( $auth['Authorization'] ) )
{
	header( $__http_status[HTTP_UNAUTHORIZED] );
	die( json_encode( $response ) );
}

// Reset value of $auth to decoded Authorization header and extract $username and signed message hash
$auth = base64_decode( $auth['Authorization'] );
list( $username, $signature ) = explode( ':', $auth );

$db = mysql::instance( $config->db[DB_MAIN] );
$query = "
	SELECT
		api_key
	FROM
		users
	WHERE
		enabled = 1 AND username = :username";

$db->execute( $query, array( 'username' => $username ) );
$key = $db->result->fetchColumn();

if( $_SERVER['REQUEST_METHOD'] === 'GET' )
{
	$data =& $_GET;
	$input = http_build_query( $data );
}
else
{
	$input = file_get_contents( 'php://input' );
	parse_str( $input, $data );
}

// This will change how a message is hashed, so any changes to this composition
// could potentially break code. see also request.class.php; changes to this
// composition must also be reflected in that class!!!
$contents = <<<EODOC
{$_SERVER['REQUEST_METHOD']} {$_SERVER['SERVER_PROTOCOL']} {$page->request}
Date: {$_SERVER['HTTP_DATE']}

$input
EODOC;

$hash = hash_hmac( 'sha1', utf8_encode( $contents ), $key );

if( $hash !== $signature )
{
	$response['data'] = $hash;
	header( $__http_status[HTTP_UNAUTHORIZED] );
	die(json_encode($response));
}

$page->params = $page->params + array(
	'method'	=>	$_SERVER['REQUEST_METHOD'],
	'data'		=>	$data
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
	$xml = new SimpleXMLElement( '<response/>' );
	array_walk_recursive( $response, function( $v, $k ) use ( $xml )
	{
		$xml->addChild( $k, $v );
	});
	$page->body = $xml->asXML();
}
else
{
	$response['status'] = HTTP_NOT_ACCEPTABLE;
	$page->body = 'Invalid format';
}

header( $__http_status[$response['status']] );
header( 'Date: ' . gmdate( 'D, d M Y H:i:s \G\M\T' ) );

if(	DEV )
{
	$_finish__ = microtime( true );
	header( 'X-Execute-Time: ' .  $_finish__ - $_start__ );
}

$page->render( $page->body );
?>
