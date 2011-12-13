<?php
/**
 *
 * @author ross paskett
 */

abstract class database extends PDO
{
	abstract public function fetchIntoObject( &$obj, $query, array $params = array() );
	abstract public function fetchClass( $class, $query, array $params = array() );
	abstract public function next();
	abstract public function execute( $query, array $params = array() );

	protected static $instance = array();


	/**
	 * Returns an instance of the database, separates database
	 * driver and db connection name as defined in config.inc.php
	 * @param string $db_name
	 * @return object
	 */

	final public static function instance( $db_info )
	{
		// convert connection info into a string and create a unique hash
		$name = md5( implode( '', $db_info ) );
		if( empty( static::$instance[$name] ) )
			static::$instance[$name] = new static( $db_info );

		return static::$instance[$name];
	}


	/**
	 * Returns a date/time string in sql-friendly format
	 * @param int $time
	 * @return string
	 */

	public function dateTime( $time = -1 )
	{
		return date('Y-m-d H:i:s', ( $time === -1 ? time() : $time ) );
	}


	/**
	 * takes an array of fields and prepares
	 * them for use in a query statement
	 * @param array $fields
	 * @return array
	 */

	public function prepareFields( &$data )
	{
		$return = array();
		foreach( $data as $k => $v )
			$return[] = "{$k} = :{$k}";

		return $return;
	} // end function prepareFields


	/**
	 * Recursively scrubs an array or string of illegal characters
	 * @param mixed $string
	 */

	public function sanitize( $string, $ignore = array() )
	{
		if ( is_array( $string ) )
		{
			$ignore = array_flip( $ignore );
			foreach ( $string as $key => $value )
			{
				if( empty( $ignore[$key] ) )
					$string[$key] = $this->sanitize( $value, $ignore );
			}
			return $string;
		}
		return parent::quote( $string );
	}	//	end function sanitize()

} // end abstract class database
?>