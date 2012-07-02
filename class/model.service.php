<?php
namespace Service;

abstract class Model
{
	protected static $domain;
	protected $get, $post, $put, $delete;
	static public $method, $ranges = array(), $options = array();


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
		global $config, $page;

		// check if a range (subset) is requested...
		if( ! empty( $_SERVER['HTTP_RANGE'] ) )
			static::$ranges = static::tokenize( $_SERVER['HTTP_RANGE'] );

		// ...and look for any modifiers/options
		if( ! empty( $_SERVER['HTTP_PRAGMA'] ) )
			static::$options = static::tokenize( $_SERVER['HTTP_PRAGMA'] );

		$domain = static::$domain;

		// pull the primary key(s) and diff against page params
		// if any key is returned, assume it's a collection
		if( $domain )
		{
			$keys = (array) $domain::getKeys('primary');

			// swap fields (values) for keys
			$keys = array_flip( $keys );

			// flip the boolean twice (array() [falsy value] -> true -> false)
			// or (array(some_key) [truish value] -> false -> true
			$collection = !! array_diff_key( $keys, $params );
			unset($keys);
		}
		else
			$collection = true;

		try
		{
			if( ! empty( $data['q'] ) )
				return static::search( $data['q'] );
			else if( $method == 'GET' && $collection )
			 	return static::collection( $method );
			else if( $method == 'GET' && $domain )
				return static::read( $params, $data );
			else if( $method == 'POST' && $domain )
				return static::create( $data );
			else if( $method == 'PUT' && $domain )
				return static::update( $params, $data );
			else if( $method == 'DELETE' && $domain )
				return static::delete( $params );
			else
				throw new RESTException(
					"Method $method not allowed at {$page->request}",
					$config->HTTP_METHOD_NOT_ALLOWED );

		}
		catch( RESTException $e )
		{
			$return = array(
				'success'	=>	'false',
				'status'	=>	$e->getCode(),
				'message'	=>	$e->getMessage()
			);

			if( $config->DEV )
			{
				if( $error = $e->getError() )
					$return['error'] = $error;

				if( $debug = $e->getDebug() )
					$return['debug'] = $debug;
			}

			return $return;
		}
	} // end method init


	public static function create( $post )
	{
		global $config;
		$domain = static::$domain;

		try
		{
			$obj = new $domain();
			$obj->capture( $post, $domain::getKeys() );
			$obj->save();

			return static::respond( $obj, $config->HTTP_CREATED );
		}
		catch( \Exception $e )
		{
			throw new RESTException( $e->getMessage(), $e->getCode() );
		}
	}

	public static function read( $id, $get )
	{
		global $config, $page;
		$domain = static::$domain;

		$obj = new $domain( $id );

		if( $obj instanceof $domain && $obj->id )
			return static::respond( $obj, $config->HTTP_OK );
		else
			throw new RESTException( 'Resource not found ' . $page->request,
				$config->HTTP_NOT_FOUND );
	}

	public static function update( $params, $put )
	{
		global $config;
		$domain = static::$domain;

		$obj = new $domain( $params );
		$obj->capture( $put, $domain::getKeys() );

		if( $obj->save() )
			return static::respond( $obj, $config->HTTP_OK );
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
			return static::respond( '', $config->HTTP_OK );
		else
			throw new RESTException( 'Unable to delete resource',
				$config->HTTP_INTERNAL_SERVER_ERROR );
	}


	/**
	 * Parse a range string into usable values
	 * @param	string	$values	an arbitrary string of ranges
	 * ex: 104-109,143; -100 (0 - 100); 100- (>=100)
	 */

	public static function parseRange( $values )
	{
		$ranges = explode( ',', $values );
		$ret = array();
		foreach( $ranges as $key => $range )
		{
			// check if a large range exists (i.e., 1-100)
			if( strpos($range, '-') !== false )
			{
				$range = explode( '-', $range );
				// don't need to check if $range[0] exists
				// if range is "-40" or similar (which means 0-40)
				// $range[0] is null and it starts from 0
				$range = range( $range[0], $range[1] );
				$ret = array_merge( $ret, $range );
			}
			else
			{
				$ret[] = intval($range);
			}
		}

		// gc
		unset( $ranges, $values, $key, $range);
		return $ret;
	}


	/**
	 * Parse a string into usable options/ranges
	 * @param	string	$option	a string to parse for options
	 * @return	array
	 */

	public static function tokenize( $option )
	{
		// split string into chunks at semi-colon or whitespace chars
		$option = preg_split( '/;\s*/', $option );
		$return = array();

		foreach( $option as $opt )
		{
			$opt = explode( '=', $opt );
			$return[$opt[0]] = ( count( $opt ) > 1 ? $opt[1] : true );
		}
		// gc
		unset( $option, $opt );

		return $return;
	} // end method tokenize


	/**
	 * Returns a collection of objects in response to a REST request
	 * @param	string	$method		the HTTP request method
	 * @return	array
	 */

	public static function collection( $method )
	{
		global $config;

		// GET is the only method allowed for collections for now
		if( $method != 'GET' )
			throw new RESTException('Collections are read-only',
				$config->HTTP_METHOD_NOT_ALLOWED);

		// make a local copy of domain name so domain
		// properties and methods can be accessed
		// [can't do static::$domain::someMethod()]
		$domain = static::$domain;

		// if domain is empty it's probably an error or someone
		// forgot to define it, so throw an error up the chain
		if( empty( $domain ) )
			throw new RESTException( 'Unable to load service interface',
				$config->HTTP_INTERNAL_SERVER_ERROR );

		$fields = $domain::getFields();

		$q = new \query;
		$status = $config->HTTP_OK; // default status

		// check for ranges and try to parse them
		if( ! empty( static::$ranges ) )
		{
			$status = $config->HTTP_PARTIAL_CONTENT;
			foreach( static::$ranges as $field => &$range )
			{
				$date_regex = '\d{4}-\d{2}-\d{2} '
					. '(([0-1][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])';
				if( 0 !== preg_match( '#^(\d*[,-][^/]?\d*-?)*$#', $range ) )
				{
					$range = static::parseRange( $range );
					$q->in( $field, $range );
				}
				else if( preg_match( "#{$date_regex}/{$date_regex}#", $range ) )
				{
					$range = explode( '/', $range );
					$q->between( $field, $range[0], $range[1] );
				}
				else
				{
					$q->where( array( $field => $range ) );
				}
			}
		}

		// check for custom options
		if( static::$options )
		{
			$options = static::$options;
			if( ! empty( $options['order'] ) )
			{
				// this is some pretty crappy hack checking, first run
				$order = explode( ',', $options['order'] );
				if( ! array_diff( $order, $fields ) )
					$q->order( $order );
				else
					throw new RESTException(
						'Field not acceptable for ordering',
						$config->HTTP_NOT_ACCEPTABLE
					);
			}

			if( ! empty( $options['limit'] ) )
				$q->limit( $options['limit'] );
		}

		$q->select( $fields, $domain::getTable() )->query();
		$db = \mysql::instance( $config->db[$config->DB_MAIN] );
		$db->execute( $q );

		if( $db->stmt->errorCode() === '00000' )
		{
			if( ! empty( $options['group'] ) )
			{
				$data = array();
				$group = explode( ',', $options['group'] );
				$db->stmt->setFetchMode( \PDO::FETCH_ASSOC );
				while( $row = $db->next() )
				{
					$d =& $data;
					// unlimited groupability, at the
					// low, low cost of compute cycles :P
					foreach( $group as $g )
						$d =& $d[$row[$g]];
					$d[] = $row;
				}
			}
			else
				$data = $db->stmt->fetchAll( \PDO::FETCH_ASSOC );

			return static::respond( $data, $status );
		}
		else
		{
			// error code and query are only in response if config::DEV is true
			// @see self::init()
			throw new RESTException(
				'Unable to retrieve data',
				$config->HTTP_BAD_REQUEST,
				$stmt->errorCode(),
				$q->query
			);
		}

	} // end method collection


	public static function search( $terms )
	{
		global $config;
		$terms = explode( ' ', $terms );
		$domain = static::$domain;
		$fields = $domain::getFields();
		$search = $domain::getSearch();
		$table = $domain::getTable();
		$q = new \query;
		$db = \mysql::instance( $config->db[$config->DB_MAIN] );
		$like = array();
		$params = array();

		foreach( $search as $field )
			foreach( $terms as $k => $term )
				$like[$field] = $term;

		$q->select( $fields, $domain::getTable() )->like( $like )->query();
		$db->execute( $q );

		if( $db->stmt->errorCode() === '00000' )
		{
			$data = $stmt->fetchAll( \PDO::FETCH_ASSOC );
			return static::respond( $data, $config->HTTP_PARTIAL_CONTENT );
		}
		else
		{
			// error code and query are only in response if config::DEV is true
			// @see self::init()
			throw new RESTException(
				'Unable to retrieve data',
				$config->HTTP_BAD_REQUEST,
				$stmt->errorCode(),
				$q->query
			);
		}

	}


	public static function respond( $data, $status = 200, $success = 'true' )
	{
		return array(
			'success' => $success,
			'status' => $status,
			'data' => $data
		);
	}

}
?>