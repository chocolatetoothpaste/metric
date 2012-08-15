<?php
namespace Service;

class RESTException extends \Exception
{
	private $error, $debug;

	public function __construct( $message, $code = 500, $error = 0, $message = null )
	{
		global $config;

		if( $error == '42S22' )
		{
			$this->debug = 'Schema does not match data model ' . \get_called_class();
			$code = $config->HTTP_INTERNAL_SERVER_ERROR;
		}
		else if( $error == '23000' )
		{
			$this->debug = 'Conflict exists among keys';
			$code = $config->HTTP_CONFLICT;
		}
		else if( $error == '42S02' )
		{
			$code = $config->HTTP_INTERNAL_SERVER_ERROR;
			$this->debug = 'Schema does not exist for data model. Check data model and connection info';
		}
		else
		{
			$this->debug = 'Unable to create resource';
			$code = $config->HTTP_BAD_REQUEST;
		}

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