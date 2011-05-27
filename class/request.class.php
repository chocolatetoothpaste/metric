<?php

class request
{
	public $method = 'GET', $headers = array(), $content = array(), $response,
		$format = 'application/json', $username, $password;

	public function __construct( $url )
	{
		$this->url = $url;
	}

	public function exec()
	{
		if( is_array( $this->content ) )
		$this->content = http_build_query( $this->content );

		if( $this->method == 'GET' && $this->content )
		{
			$this->url .= "?{$this->content}";
			$this->content = '';
		}
		
		$this->hash();

		$headers = array_merge($this->headers, array(
			'Content-Type: application/x-www-form-urlencoded',
			'Connection: close',
			"Accept: {$this->format}",
			"Authorization: {$this->username}:{$this->hash}",
			"Date: {$this->date}"
		));

		$options = array(
			CURLOPT_URL				=>	$this->url,
			CURLOPT_RETURNTRANSFER	=>	1,
			CURLOPT_HTTPHEADER		=>	$headers,
			CURLOPT_CUSTOMREQUEST	=>	$this->method,
			CURLOPT_POSTFIELDS		=>	$this->content,
			CURL_HTTP_VERSION_1_1
		);

		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$this->response = curl_exec($ch);
		curl_close($ch);
	}

	private function hash()
	{
		$url = str_replace( URL_API, '', $this->url );
		$this->date = gmdate('D, d M Y H:i:s \G\M\T');
		
		$this->hash = "{$this->method} HTTP/1.1 {$url}\n"
			. "Date: {$this->date}\n\n"
			. "{$this->content}";
		$this->hash = hash_hmac( 'sha1', $this->hash, $this->key );
	}

}
?>
