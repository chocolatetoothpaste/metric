<?php
class request extends \HttpRequest
{
	public $auth, $length, $response, $key, $host;

	public function __construct( $url = '' )
	{
		global $config;
		parent::__construct( $url );
		$this->setHeaders( array(
			'Connection' => 'close',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Accept' => 'application/json'
		) );
	}

	public function git( $data = null )
	{
		$this->setQueryData( $data );
		$this->prepare( \HttpRequest::METH_GET, $data );
		$this->send();

		return $this;
	}

	public function post( $data = array() )
	{
		$this->setPostFields( $data );
		$this->prepare( \HttpRequest::METH_POST );
		$this->send();

		return $this;
	}

	public function put( array $data = array() )
	{
		$this->setPutData( http_build_query( $data ) );
		$this->prepare( \HttpRequest::METH_PUT );
		$this->send();

		return $this;
	}

	public function delete()
	{
		$this->prepare( \HttpRequest::METH_DELETE );
		$this->send();

		return $this;
	}

	public function prepare( $method )
	{
		$this->setMethod( $method );
		//$this->hash();
	}

	public function range( array $ranges )
	{
		$range = '';
		foreach( $ranges as $token => $value )
			$range .= ( ! empty( $value ) ? "$token=$value; " : "$token; " );

		$this->addHeaders( array( 'Range' => $range ) );

		return $this;
	}

	public function options( array $options )
	{
		$option = '';
		foreach( $options as $token => $value )
			$option .= "$token=$value; ";
		$this->addHeaders( array( 'Pragma' => $option ) );

		return $this;
	}

	public function getPostData()
	{
		return http_build_query( $this->getPostFields() );
	}

	public function decode( $assoc = false )
	{
		if( strtolower( $this->getResponseHeader( 'Content-Type' ) ) == 'application/json' )
			$this->response = json_decode( $this->getResponseBody(), $assoc );
		return $this->response;
	}

	private function hash()
	{
		$this->date = gmdate( DATE_RFC1123 );

		// create the hashable request document and run through hash function
		$this->hash = $this->getMethod() . ' ' . $this->getUrl() . " HTTP/1.1\n"
			. "Date: {$this->date}\n";
		if( $this->getMethod() == \HttpRequest::METH_GET )
			$this->hash .= "Content-Length: {$this->length}\n\n"
				. $this->getPostData();

		$this->hash = hash_hmac( 'sha1', $this->hash, $this->key );

		// make message hash transferrable
		$this->auth = base64_encode( "asd:{$this->hash}" );

		// set auth header
		$this->addHeaders( array(
			'Authorization' => $this->auth,
			'Date'			=> $this->date
		) );
	}

	/*
	public function exec()
	{
		$this->length = 0;
		$range = $options = '';

		$curlopt = array(
			CURLOPT_RETURNTRANSFER	=>	1,
			CURLOPT_CUSTOMREQUEST	=>	$this->method,
			CURLOPT_SSL_VERIFYPEER	=>	0,
			CURL_HTTP_VERSION_1_1
		);

		// if $this->content has length, then handle for GET or non-get methods
		if( $this->content )
		{
 			if( $this->method === 'GET' )
 			{
				$this->url .= "?{$this->content}";
			}
			else
			{
				$curlopt[CURLOPT_POSTFIELDS] = $this->content;
			}
			$this->length = strlen( $this->content );
		}
		$this->_headers['X-Content-Length'] = $this->length;

		if( $this->range )
		{
			foreach( $this->range as $token => $value )
				$range .= "$token=$value; ";
			$this->_headers['Range'] = $range;
		}

		if( $this->options )
		{
			foreach( $this->options as $token => $value )
				$options .= "$token=$value; ";
			$this->_headers['Pragma'] = $options;
		}

		$this->hash();
		$headers = array_merge( $this->headers, $this->_headers );

		foreach( $headers as $k => &$v )
			$v = "$k: $v";

		$curlopt[CURLOPT_HTTPHEADER] = $headers;
		$curlopt[CURLOPT_URL] = $this->host . $this->url;

		$ch = curl_init();
		curl_setopt_array($ch, $curlopt);
		$this->response = curl_exec($ch);
		curl_close($ch);
	}
	*/

}
?>