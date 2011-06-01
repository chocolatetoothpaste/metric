<?php
class request
{
	public $method = 'GET', $headers = array(), $content = array(), $auth,
		$response, $format = 'application/json', $username, $password, $host;

	public function __construct( $url )
	{
		$this->url = $url;
		$this->host = URL_API;
	}

	public function exec()
	{
		if( is_array( $this->content ) )
			$this->content = http_build_query( $this->content );

		$this->hash();
		$this->auth = base64_encode( "{$this->username}:{$this->hash}" );

		$headers = array_merge($this->headers, array(
			'Content-Type: application/x-www-form-urlencoded',
			'Connection: close',
			"Accept: {$this->format}",
			"Authorization: {$this->auth}",
			"Date: {$this->date}"
		));

		$options = array(
			CURLOPT_RETURNTRANSFER	=>	1,
			CURLOPT_HTTPHEADER		=>	$headers,
			CURLOPT_CUSTOMREQUEST	=>	$this->method,
			CURL_HTTP_VERSION_1_1
		);

		if( $this->content )
		{
 			if( $this->method === 'GET' )
				$this->url .= "?{$this->content}";
			else
			{
				$options[CURLOPT_POSTFIELDS] = $this->content;
				//$this->length = strlen( $this->content );
				$headers[] = "Content-Length: {$this->content}";
			}
		}

		$options[CURLOPT_URL] = $this->host . $this->url;

		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$this->response = curl_exec($ch);
		curl_close($ch);
	}

	private function hash()
	{
		//$this->date = gmdate('D, d M Y H:i:s \G\M\T');
		$this->date = gmdate(DATE_RFC1123);
		
		$this->hash = "{$this->method} HTTP/1.1 {$this->url}\n"
			. "Date: {$this->date}\n\n"
			//. "Content-Length: {$this->length}\n\n"
			. "{$this->content}";
		$this->hash = hash_hmac( 'sha1', $this->hash, $this->key );
	}

}
?>
