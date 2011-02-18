<?php

// Informational
define('HTTP_CONTINUE', 100);
define('HTTP_SWITCHING_PROTOCOLS', 101);

// Successful
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_ACCEPTED', 202);
define('HTTP_NONAUTHORITATIVE_INFORMATION', 203);
define('HTTP_NO_CONTENT', 204);
define('HTTP_RESET_CONTENT', 205);
define('HTTP_PARTIAL_CONTENT', 206);

// Redirection
define('HTTP_MULTIPLE_CHOICE', 300);
define('HTTP_MOVED_PERMANENTLY', 301);
define('HTTP_FOUND', 302);
define('HTTP_SEE_OTHER', 303);
define('HTTP_NOT_MODIFIED', 304);
define('HTTP_USE_PROXY', 305);
define('HTTP_TEMPORARY_REDIRECT', 307);

// Client Error
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_PAYMENT_REQUIRED', 402);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_NOT_ACCEPTABLE', 406);
define('HTTP_PROXY_AUTHENTICATION_REQUIRED', 407);
define('HTTP_REQUEST_TIMEOUT', 408);
define('HTTP_CONFLICT', 409);
define('HTTP_GONE', 410);
define('HTTP_LENGTH_REQUIRED', 411);
define('HTTP_PRECONDITION_FAILED', 412);
define('HTTP_REQUEST_ENTITY_TOO_LARGE', 413);
define('HTTP_REQUEST_URI_TOO_LONG', 414);
define('HTTP_UNSUPPORTED_MEDIA_TYPE', 415);
define('HTTP_REQUESTED_RANGE_NOT_SATISFIABLE', 416);
define('HTTP_EXPECTATION_FAILED', 417);

// Server Error
define('HTTP_INTERNAL_SERVER_ERROR', 500);
define('HTTP_NOT_IMPLEMENTED', 501);
define('HTTP_BAD_GATEWAY', 502);
define('HTTP_SERVICE_UNAVAILABLE', 503);
define('HTTP_GATEWAY_TIMEOUT', 504);
define('HTTP_HTTP_VERSION_NOT_SUPPORT', 505);


$__http_status = array(
	HTTP_OK						=>	'HTTP/1.1 200 Ok',
	HTTP_CREATED				=>	'HTTP/1.1 201 Created',
	HTTP_PARTIAL_CONTENT		=>	'HTTP/1.1 206 Partial Contenta',
	HTTP_NOT_MODIFIED			=>	'HTTP/1.1 304 Not Modified',
	HTTP_UNAUTHORIZED			=>	'HTTP/1.1 401 Unauthorized',
	HTTP_NOT_FOUND				=>	'HTTP/1.1 404 Not Found',
	HTTP_NOT_ACCEPTABLE			=>	'HTTP/1.1 406 Not Acceptable',
	HTTP_EXPECTATION_FAILED		=>	'HTTP/1.1 417 Expectation Failed',
	HTTP_INTERNAL_SERVER_ERROR	=>	'HTTP/1.1 500 Internal Server Error',
	HTTP_NOT_IMPLEMENTED		=>	'HTTP/1.1 501 Not Implemented'

);

?>