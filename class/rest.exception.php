<?php
namespace Service;

class RESTException extends \Exception
{
	private $error, $debug;

	public function __construct( $message, $code = 500, $error = null, $debug = null )
	{

		if( $error )
		{
			if( $error == '42S02' )
				$debug = 'Schema does not exist for data model';
			else if( $error == '42S22' )
				$debug = 'Schema does not match data model';
		}

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