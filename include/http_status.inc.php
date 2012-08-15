<?php

// Informational
$config->define('HTTP_CONTINUE', 100);
$config->define('HTTP_SWITCHING_PROTOCOLS', 101);

// Successful
$config->define('HTTP_OK', 200);
$config->define('HTTP_CREATED', 201);
$config->define('HTTP_ACCEPTED', 202);
$config->define('HTTP_NONAUTHORITATIVE_INFORMATION', 203);
$config->define('HTTP_NO_CONTENT', 204);
$config->define('HTTP_RESET_CONTENT', 205);
$config->define('HTTP_PARTIAL_CONTENT', 206);

// Redirection
$config->define('HTTP_MULTIPLE_CHOICE', 300);
$config->define('HTTP_MOVED_PERMANENTLY', 301);
$config->define('HTTP_FOUND', 302);
$config->define('HTTP_SEE_OTHER', 303);
$config->define('HTTP_NOT_MODIFIED', 304);
$config->define('HTTP_USE_PROXY', 305);
$config->define('HTTP_TEMPORARY_REDIRECT', 307);

// Client Error
$config->define('HTTP_BAD_REQUEST', 400);
$config->define('HTTP_UNAUTHORIZED', 401);
$config->define('HTTP_PAYMENT_REQUIRED', 402);
$config->define('HTTP_FORBIDDEN', 403);
$config->define('HTTP_NOT_FOUND', 404);
$config->define('HTTP_METHOD_NOT_ALLOWED', 405);
$config->define('HTTP_NOT_ACCEPTABLE', 406);
$config->define('HTTP_PROXY_AUTHENTICATION_REQUIRED', 407);
$config->define('HTTP_REQUEST_TIMEOUT', 408);
$config->define('HTTP_CONFLICT', 409);
$config->define('HTTP_GONE', 410);
$config->define('HTTP_LENGTH_REQUIRED', 411);
$config->define('HTTP_PRECONDITION_FAILED', 412);
$config->define('HTTP_REQUEST_ENTITY_TOO_LARGE', 413);
$config->define('HTTP_REQUEST_URI_TOO_LONG', 414);
$config->define('HTTP_UNSUPPORTED_MEDIA_TYPE', 415);
$config->define('HTTP_REQUESTED_RANGE_NOT_SATISFIABLE', 416);
$config->define('HTTP_EXPECTATION_FAILED', 417);

// Server Error
$config->define('HTTP_INTERNAL_SERVER_ERROR', 500);
$config->define('HTTP_NOT_IMPLEMENTED', 501);
$config->define('HTTP_BAD_GATEWAY', 502);
$config->define('HTTP_SERVICE_UNAVAILABLE', 503);
$config->define('HTTP_GATEWAY_TIMEOUT', 504);
$config->define('HTTP_HTTP_VERSION_NOT_SUPPORT', 505);


$config->http_status = array(
	$config->HTTP_OK					=>	'HTTP/1.1 200 Ok',
	$config->HTTP_CREATED				=>	'HTTP/1.1 201 Created',
	$config->HTTP_ACCEPTED				=>	'HTTP/1.1 202 Accepted',
	$config->HTTP_PARTIAL_CONTENT		=>	'HTTP/1.1 206 Partial Content',
	$config->HTTP_NOT_MODIFIED			=>	'HTTP/1.1 304 Not Modified',
	$config->HTTP_BAD_REQUEST			=>	'HTTP/1.1 400 Bad Request',
	$config->HTTP_UNAUTHORIZED			=>	'HTTP/1.1 401 Unauthorized',
	$config->HTTP_NOT_FOUND				=>	'HTTP/1.1 404 Not Found',
	$config->HTTP_METHOD_NOT_ALLOWED	=>	'HTTP/1.1 405 Method Not Allowed',
	$config->HTTP_NOT_ACCEPTABLE		=>	'HTTP/1.1 406 Not Acceptable',
	$config->HTTP_CONFLICT				=>	'HTTP/1.1 409 Conflict',
	$config->HTTP_EXPECTATION_FAILED	=>	'HTTP/1.1 417 Expectation Failed',
	$config->HTTP_INTERNAL_SERVER_ERROR	=>	'HTTP/1.1 500 Internal Server Error',
	$config->HTTP_NOT_IMPLEMENTED		=>	'HTTP/1.1 501 Not Implemented'
);

?>