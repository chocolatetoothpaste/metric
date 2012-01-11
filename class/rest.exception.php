<?php
namespace Service;

class RESTException extends \Exception
{
	private $error, $debug;
	public function __construct( $message, $code = 500, $error = null, $debug = null )
	{
		$this->error = $error;
		$this->debug = $debug;

		parent::__construct( $message, $code );
	}

	public function getDebug()
	{
		return $this->debug;
	}

	public function getError()
	{
		return $this->error;
	}
}

?>