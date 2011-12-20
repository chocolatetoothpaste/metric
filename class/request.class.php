<?php
class request
{
	public $method, $headers = array(), $content,
		$auth, $length, $response, $format = 'application/json',
		$username, $password, $host;

	private $_headers = array();

	public function __construct( $url, $method = 'GET' )
	{
		global $config;
		$this->url = $url;
		$this->method = $method;
		// @see config.inc.php
		$this->host = $config->URL_API;
		$this->_headers['Connection'] = 'close';
		$this->_headers['Content-Type'] = 'application/x-www-form-urlencoded';
	}

	public function exec()
	{
		$this->_headers['Accept'] = $this->format;
		$this->length = 0;

		$options = array(
			CURLOPT_RETURNTRANSFER	=>	1,
			CURLOPT_CUSTOMREQUEST	=>	$this->method,
			CURL_HTTP_VERSION_1_1
		);

		// if $this->content has length, then handle for GET or non-get methods
		if( $this->content )
		{
 			if( $this->method === 'GET' )
				$this->url .= "?{$this->content}";
			else
			{
				$options[CURLOPT_POSTFIELDS] = $this->content;
			}
			$this->length = strlen( $this->content );
		}
		$this->_headers['Content-Length'] = $this->length;

		$this->hash();
		$headers = array_merge( $this->headers, $this->_headers );

		foreach( $headers as $k => &$v )
			$v = "$k: $v";

		$options[CURLOPT_HTTPHEADER] = $headers;
		$options[CURLOPT_URL] = $this->host . $this->url;

		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$this->response = curl_exec($ch);
		curl_close($ch);
	}

	private function hash()
	{
		$this->date = gmdate(DATE_RFC1123);
		$this->_headers['Date'] = $this->date;

		// create the hashable request document and run through hash function
		$this->hash = "{$this->method} {$this->url} HTTP/1.1\n"
			. "Date: {$this->date}\n"
			. "Content-Length: {$this->length}\n\n"
			. "{$this->content}";
		$this->hash = hash_hmac( 'sha1', $this->hash, $this->key );

		// make message hash transferrable
		$this->auth = base64_encode( "{$this->username}:{$this->hash}" );

		// set auth header
		$this->_headers['Authorization'] = $this->auth;
	}

}
?>