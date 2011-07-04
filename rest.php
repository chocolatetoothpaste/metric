<?php
$page->template = false;
$page->content_type = $_SERVER['HTTP_ACCEPT'];
$response = array( 'success' => 'false', 'status' => HTTP_UNAUTHORIZED );
/*
// Authorization header is hidden from PHP's
// $_SERVER super global, so grab it from apache
$auth = apache_request_headers();

// Make sure credentials were passed, otherwise
// there's no point in going any further
if( empty( $auth['Authorization'] ) )
{
	header( $__http_status[$response['status']] );
	header( 'WWW-Authenticate: Signature realm="Calendar"' );
	die( json_encode( $response ) );
}

// Reset value of $auth to decoded Authorization
// header and extract $username and signed message hash
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
*/
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

// Grab the length of the actual request to make sure it hasn't been modified
// in transit. This is safer than relying on the Content-Length header since
// it could be spoofed. It won't matter though, if the request wasn't modified,
// the lenghts will match and the signature will match
$length = strlen( $input );

// This will change how a message is hashed, so any changes to this composition
// could potentially break code. see also request.class.php; changes to this
// composition must also be reflected in that class!!!
/*
$doc = <<<EODOC
{$_SERVER['REQUEST_METHOD']} {$page->request} {$_SERVER['SERVER_PROTOCOL']}
Date: {$_SERVER['HTTP_DATE']}
Content-Length: {$length}

$input
EODOC;

$hash = hash_hmac( 'sha1', utf8_encode( $doc ), $key );

if( $hash !== $signature )
{
	$response['response'] = $hash;
	header( $__http_status[$response['status']] );
	die(json_encode($response));
}
*/
$page->params = $page->params + array(
	'method'	=>	$_SERVER['REQUEST_METHOD'],
	'data'		=>	$data
);

//error_log(print_r($page->callback,true));
$response = call_user_func_array( $page->callback, $page->params );

//error_log(print_r($page->params,true));
//error_log(print_r($response,true));

// determine reponse format and set up response
if( $page->content_type === 'application/json' )
{
	$page->body = json_encode( $response );
}
elseif( $page->content_type === 'application/xml' )
{
	/*$xml = new SimpleXMLElement( '<response/>' );
	array_walk_recursive( $response, function( $v, $k ) use ( $xml )
	{
		$xml->addChild( $k, $v );
	});
	$page->body = $xml->asXML();*/
}
else
{
	$response['status'] = HTTP_NOT_ACCEPTABLE;
	$page->body = 'Invalid format';
}

header( $__http_status[$response['status']] );
header( 'Date: ' . gmdate( DATE_RFC1123 ) );

if(	DEV )
{
	$_finish__ = microtime( true );
	header( 'X-Execute-Time: ' .  $_finish__ - $_start__ );
}

$page->render( $page->body );
?>
