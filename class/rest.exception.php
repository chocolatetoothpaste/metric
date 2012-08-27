<?php
namespace Service;

class RESTException extends \Exception
{
	private $error, $debug;

	public function __construct( $message, $status = 500, $error = 0, $debug = null )
	{
		$this->error = $error;
		$this->debug = $debug;

		$err_info =& \mysql::$err_info;

		if( ! empty( $err_info[$error] ) )
		{
			$this->debug = $message;
			$message = $err_info[$error]['message'];
			$status = $err_info[$error]['code'];
		}

		parent::__construct( $message, $status );
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