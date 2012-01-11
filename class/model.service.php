<?php
namespace Service;

class RESTException extends \Exception { }

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

	public static function init( $method = 'GET', $params = array(), $data = array() )
	{
		global $config;
		// determine http request method and call the proper static method.
		// this method is only called by child classes, service model is never
		// instantiated. see child service models for usage and implementation
		if( !empty( $_SERVER['HTTP_CONTENT_RANGE'] ) )
			$ranges = $_SERVER['HTTP_CONTENT_RANGE'];
		elseif( !empty( $_SERVER['HTTP_RANGE'] ) )
			$ranges = $_SERVER['HTTP_RANGE'];
		else
			$ranges = null;

		$options = ( empty( $_SERVER['HTTP_PRAGMA'] ) ? null : $_SERVER['HTTP_PRAGMA'] );

		try
		{
			if( $method == 'GET' && empty( $params['id'] ) )
				return static::collection( $method, $ranges, $options );
			elseif( $method == 'GET' )
				return static::read( $params, $data );
			elseif( $method == 'POST' )
				return static::create( $data );
			elseif( $method == 'PUT' )
				return static::update( $params, $data );
			elseif( $method == 'DELETE' )
				return static::delete( $params );
			else
				throw new RESTException('Invalid method received: ' . $method,
					$config->HTTP_METHOD_NOT_ALLOWED );
		}
		catch( RESTException $e )
		{
			return array(
				'success'	=>	'false',
				'status'	=>	$e->getCode(),
				'message'	=>	$e->getMessage()
			);
		}
	} // end method init


	public static function create( $post )
	{
		global $config;
		$domain = static::$domain;
		$obj = new $domain();
		$obj->capture( $post, $domain::getKeys() );

		if( $obj->save() )
			return array(
				'success'	=>	'true',
				'response'	=>	$obj,
				'status'	=>	$config->HTTP_CREATED
			);
		else
			throw new RESTException( 'Unable to create resource',
				$config->HTTP_NOT_ACCEPTABLE );
	}

	public static function read( $id, $get )
	{
		global $config, $page;
		$domain = static::$domain;
		$obj = new $domain( $id['id'] );

		if( $obj instanceof $domain && $obj->id )
			return array(
				'success'	=>	'true',
				'response'	=>	$obj,
				'status'	=>	$config->HTTP_OK
			);
		else
		{
			$class = explode( '\\', get_called_class() );
			throw new RESTException( 'Unable to locate the service '
				. end( $class ) . ' at ' . $page->request,
				$config->HTTP_NOT_FOUND );
		}
	}

	public static function update( $params, $put )
	{
		global $config;
		$domain = static::$domain;
		$obj = new $domain( $params['id'] );
		$obj->capture( $put, $domain::getKeys() );

		if( $obj->save() )
			return array(
				'success'	=>	'true',
				'response'	=>	$obj,
				'status'	=>	$config->HTTP_OK
			);
		else
			throw new RESTException( 'Unable to update resource',
				$config->HTTP_INTERNAL_SERVER_ERROR );
	}

	public static function delete( $params )
	{
		global $config;
		$domain = static::$domain;
		$obj = new $domain( $params );

		if( $obj->delete() )
			$message = array(
				'success'	=>	'true',
				'response'	=>	$obj,
				'status'	=>	$config->HTTP_OK
			);
		else
			throw new RESTException( 'Unable to delete resource',
				$config->HTTP_INTERNAL_SERVER_ERROR );
	}


	/**
	 *
	 */

	public static function parseRange( $values )
	{
		$ranges = explode(',', $values);
		$ret = array();
		foreach( $ranges as $key => $range )
		{
			if( strpos($range, '-') !== false )
			{
				$range = explode('-', $range);
				if(empty($range[0]))
					$range[0] = '1';

				$range = range($range[0], $range[1]);
				$ret = array_merge($ret,$range);
			}
			else
			{
				$ret[] = intval($range);
			}
		}
		unset( $ranges, $values, $key, $range);
		return $ret;
	}


	/**
	 * Loop through an array and look for strings that can be parsed into
	 * ranges and build a query of the appropriate type
	 * @param	array	$ranges
	 * @return	array	$return
	 */

	public static function getRanges( array $ranges )
	{
		//$return = array();
		//foreach( $ranges as $field => $range )
		foreach( $ranges as $field => &$range )
		{
			if( !empty( $range ) || $range == 0 )
			{
				$date_regex = '\d{4}-\d{2}-\d{2} '
					. '(([0-1][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])';
				if( 0 !== preg_match( '#^(\d*[,-][^/]?\d*-?)*$#', $range ) )
				{
					//error_log("$range");
					$range = static::parseRange($range);
					$range = implode( ',', $range );
					$range = "$field IN ({$range})";
					//error_log("$return[$field]");
					//die;
				}
				elseif( preg_match( "#{$date_regex}/{$date_regex}#", $range ) )
				//if( strpos( $range, '/' ) !== false )
				{
					$range = str_replace( '/', '\' AND \'', $range );
					$range = "$field BETWEEN '$range'";
				}
				else
				{
					$range = "{$field}='{$range}'";
				}
			}
		}
		return $ranges;
		//return $return;
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
	 * Returns a collection of objects in response to a REST request
	 * @param	string	$method		the HTTP request method
	 * @param	string	$ranges		string of units/ranges to fetch
	 * @param	string	$options	string of options to parse
	 * @return	array
	 */

	public static function collection( $method, $ranges = '', $options = '' )
	{
		global $config;

		// GET is the only method allowed for collections for now
		if( $method != 'GET' )
			throw new RESTException('', $config->HTTP_METHOD_NOT_ALLOWED);

		// static::$domain is defined in individual services
		$domain = static::$domain;

		// if $domain is empty, there probably isn't a correpsonding domain
		// object, there isn't a service object, static::$domain isn't defined
		// yet, or all of the above
		if( empty($domain) )
			throw new RESTException('Interface to database not found',
				$config->HTTP_INTERNAL_SERVER_ERROR );

		try
		{
			$fields = $domain::getFields();
		}
		catch( \Exception $e )
		{
			throw new RESTException( 'Unable to load service interface',
				$config->HTTP_INTERNAL_SERVER_ERROR );
		}

		$q = new \query;
		$status = $config->HTTP_OK; // default status

		// check for ranges
		if( $ranges )
		{
			$ranges = static::tokenize( $ranges );
			//~ if( !empty( $ranges ) )
			//~ {
				$ranges = static::getRanges( $ranges );
				/*// left here for debugging
				return array(
					'status'	=>	$config->HTTP_OK,
					'message'	=>	'range parsing issue',
					'data'		=>	$ranges
				);//*/

				$status = $config->HTTP_PARTIAL_CONTENT;
				$q->where = implode(' AND ', $ranges );
			//~ }
		}

		// check for custom options
		if( $options )
		{
			$options = static::tokenize( $options );
			if( !empty( $options['order'] ) )
			{
				// this is some pretty crappy hack checking, first run
				$order = explode(',', $options['order']);
				if( !array_diff( $order, $fields ) )
					$q->order = implode(', ', $order);
				else
					throw new RESTException( 'Field not acceptable for ordering',
						$config->HTTP_NOT_ACCEPTABLE );
			}
		}

		/*// left here for debugging
		return array(
			'status'	=>	$config->HTTP_OK,
			'message'	=>	'must be a range issue',
			'data'		=>	$ranges
		);//*/

		/*// left here for debugging
		return array(
			'status'	=>	$config->HTTP_OK,
			'message'	=>	'query object',
			'data'		=>	$q,
			'extra'		=>	$ranges
		);//*/

		$q->select( $fields, $domain::getTable() );
		/*// left here for debugging
		return array(
			'status' => $config->HTTP_OK,
			'message' => 'query object',
			'data' => $q
		);//*/

		$db = \mysql::instance( $config->db[$config->DB_MAIN] );
		$db->quote($q->query);
		$stmt = $db->execute( $q->query, $q->params );

		/*// left here for debugging
		return array(
			'status'	=>	$config->HTTP_OK,
			'message'	=>	'statement',
			'data'		=>	$stmt->fetchAll( \PDO::FETCH_ASSOC )
		);//*/

		if( $stmt && $stmt->errorCode() === '00000' )
		{
			$data = array();
			if( !empty( $options['group'] ) )
			{
				$group = explode( ',', $options['group'] );
				while( $row = $stmt->fetch( \PDO::FETCH_ASSOC,
					\PDO::FETCH_ORI_NEXT ) )
				{
					if( !empty( $group[1] ) )
						$d =& $data[$row[$group[0]]][$row[$group[1]]][];
					else
						$d =& $data[$row[$group[0]]][];
					$d = $row;
				}
			}
			else
			{
				$data = $stmt->fetchAll( \PDO::FETCH_ASSOC );
			}

			return array(
				'success'	=>	'true',
				'response'	=>	$data,
				'status'	=>	$status
			);
		}
		else
			throw new RESTException('Unable to retrieve data' . $q->query . $stmt->errorInfo(),
				$config->HTTP_BAD_REQUEST);
	} // end method collection
}
?>