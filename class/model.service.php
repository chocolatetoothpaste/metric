<?php
namespace Service;

abstract class Model
{
	protected static $domain;
	protected $get, $post, $put, $delete;
	static public $method;


	/**
	 * Processes a request from the rest server and dispatches the request to
	 * the appropriate method
	 * @param	int		$id		a primary key used by the delegate method
	 * @param	string	$method	the HTTP request method
	 * @param	array	$data	an array of data used by the delegate method
	 * @return	array			the response generated by the delegate method
	 */
	
	public static function init( $id, $method = 'GET', $data = array() )
	{
		// determine http request method and call the proper static method.
		// this method is only called by child classes, service model is never
		// instantiated. see child service models for usage and implementation
		if( $method == 'GET' )
			return static::read( $id );
		elseif( $method == 'POST' )
			return static::create( $data );
		elseif( $method == 'PUT' )
			return static::update( $id, $data );
		elseif( $method == 'DELETE' )
			return static::delete( $id );
		else
			return array( 'success' => 'false', 'status' => HTTP_NOT_IMPLEMENTED );
	} // end method init

	
	/**
	 * Loop through an array and look for strings that can be parsed into
	 * ranges and build a query of the appropriate type
	 * @param	array	$ranges
	 * @return	array	$return
	 */

	public static function getRanges( array $ranges )
	{
		$return = array(); 
		foreach( $ranges as $field => $range )
		{
			if( !empty( $range ) || $range == 0 )
			{
//				if( preg_match( '#^(\d*[,-]?\d*-?)*$#', $range ) && false === strpos( $range, '/' ) )
				if( false !== strpos( $range, ',' ) )
				{
//					error_log("$range");
					$return[$field] = parseRange($range);
					$return[$field] = implode( ',', $return[$field] );
					$return[$field] = "$field IN ({$return['field']})";
//					error_log("$return[$field]");
//					die;
				}
				elseif( false !== strpos( $range, '/' ) )
				{
					$range = str_replace( '/', '\' AND \'', $range );
					$return[$field] = "$field BETWEEN '$range'";
				}
				else
				{
					$return[$field] = $range;
					$return[$field] = "{$field}={$range}";
				}
			}
		}
		return $return;
	} // end method getRanges

	
	/**
	 * Parse a string (typically the HTTP_PRAGMA header) for options to
	 * manipulate data structure
	 * @param	string	$options	a string to parse for options
	 */

	public static function tokenize( $options )
	{
		$options = preg_split( '/;\s?/', $options );
		$option = array();
		foreach( $options as $opt )
		{
			if( $opt )
			{
				$opt = explode( '=', $opt );
				$option[$opt[0]] = $opt[1];
			}
		}
		unset( $options, $opt );
		return $option;
	} // end method tokenize


	/**
	 * Restricts the fields that can be queried, usually based on fields in the
	 * corresponding domain object
	 * @param	array	$options
	 * @param	array	$filter
	 * @return	array
	 */
	
	protected static function filterOptions( array $options, array $filter )
	{
		return array_intersect_key( $options, $filter );
	} // end method filterOptions


	/**
	 * Returns a collection of objects in response to a REST request
	 * @param	string	$method	the HTTP request method
	 * @param	array	$get	the HTTP query
	 * @return	array
	 */

	public static function collection( $method, $get = array() )
	{
		global $config;

		// GET is the only method allowed for collections for now
		if( $method != 'GET' )
			return array( 'success' => 'false', 'status' => HTTP_METHOD_NOT_ALLOWED );

		// static::$domain is defined in individual services
		$domain = static::$domain;
		$fields = $domain::getFields();
		$q = new \query;
		$true_status = HTTP_OK; // default status

		// check for ranges and custom options
		if( !empty( $_SERVER['HTTP_RANGE'] ) )
			$ranges = static::tokenize( $_SERVER['HTTP_RANGE'] );
		if( !empty( $_SERVER['HTTP_PRAGMA'] ) )
			$options = static::tokenize( $_SERVER['HTTP_PRAGMA'] );
		
		/*//
		return array(
			'status'	=>	HTTP_OK,
			'message'	=>	'must be a range issue',
			'data'		=>	$ranges
		);//*/
		
		if( !empty( $ranges ) )
		{
			$ranges = static::getRanges( $ranges );
			/*//
			return array(
				'status'	=>	HTTP_OK,
				'message'	=>	'range parsing issue',
				'data'		=>	$ranges
			);//*/

			$true_status = HTTP_PARTIAL_CONTENT;
			$q->where = implode(' AND ', $ranges );
		}

		if( !empty( $options['order'] ) )
		{
			$q->where .= " ORDER BY :order_by";
			$q->params['order_by'] = $options['order'];
		}

		/*//
		return array(
			'status'	=>	HTTP_OK,
			'message'	=>	'query object',
			'data'		=>	$q,
			'extra'		=>	$ranges
		);//*/

		$q->select( $fields, $domain::getTable() );

		//return array('status' => HTTP_OK, 'message' => 'query object', 'data' => $q);

		$db = \mysql::instance( $config->db[DB_MAIN] );
		$db->quote($q->query);
		$stmt = $db->execute( $q->query, $q->params );

		/*//
		return array(
			'status'	=>	HTTP_OK,
			'message'	=>	'statement',
			'data'		=>	$stmt->fetchAll( \PDO::FETCH_ASSOC )
		);//*/

		if( $stmt )
		{
			if( !empty( $options ) )
			{
				$data = array();

				// check to see if a custom indexing scheme was requested
				$options = static::tokenize( $_SERVER['HTTP_PRAGMA'] );
				if( !empty( $options['group'] ) && !empty( $options['index'] ) )
				{
					while( $row = $stmt->fetch( \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT ) )
					{
						$data[$row[$options['group']]][$row[$options['index']]] = $row;
					}
				}
				elseif( !empty( $options['index'] ) )
				{
					while( $row = $stmt->fetch( \PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT ) )
					{
						$data[$row[$options['index']]] = $row;
					}
				}
			}
			else
			{
				$data = $stmt->fetchAll( \PDO::FETCH_ASSOC );
			}

			$message = array(
				'success'	=>	'true',
				'data'		=>	$data,
				'status'	=>	$true_status
			);
		}
		else
			$message = array(
				'success'	=>	'false',
				'status'	=>	HTTP_BAD_REQUEST
			);

		return $message;
	} // end method collection

}
?>
