<?php
namespace Service;

abstract class Collection
{
	/**
	 * Returns a collection of objects in response to a REST request
	 * @param	string	$method		the HTTP request method
	 * @return	array
	 */

	public static function getCollection( array $params, $separator = 'AND' )
	{
		global $config;

		// if( empty( $params ) )
		// 	throw new RESTException('No data requested',
		// 		$config->HTTP_EXPECTATION_FAILED );

		// static::$ranges = array_merge( static::$ranges, $params );

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
		$connection = $domain::getConnection();

		$q = new \query;
		$q->where( $params, $separator );
		$status = $config->HTTP_OK; // default status

		// check for ranges and try to parse them
		if( ! empty( static::$ranges ) )
		{
			$status = $config->HTTP_PARTIAL_CONTENT;
			$ranges = preg_split( '/;\s*/', static::$ranges, -1,
				PREG_SPLIT_NO_EMPTY );

			foreach( $ranges as &$range )
			{
				// <=, >=, !=, <>, and = are the accepted operators, so 1 or 2
				// matches are checked
				$reg = '/([!<>]?=|[<>]|\&{1,2})/';
				$range = preg_split( $reg, $range, -1,
					PREG_SPLIT_DELIM_CAPTURE );

				//error_log( print_r($range, true));
				$date_regex = '\d{4}-\d{2}-\d{2} '
					. '(([0-1][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])';

				// check if range is numeric
				if( 0 !== preg_match( '#^(\d*[,-][^/]?\d*-?)*$#', $range[2] ) )
				{
					$range[2] = static::parseRange( $range[2] );
					$q->where( $range[0] )->in( $range[2] );
				}

				// check if range is dates
				else if( preg_match( "#{$date_regex}/{$date_regex}#",
					$range[2] ) )
				{
					$range[2] = explode( '/', $range[2] );
					$q->where( $range[0] )->between( $range[2][0],
						$range[2][1] );
				}

				else
				{
					$q->where( $range[0] . $range[1] . $range[2] );
				}
			}
		}

		// check for custom options
		if( static::$options )
		{
			$options = static::$options;

			// basic searchability in 6 lines!
			if( ! empty( $options['search'] ) )
			{
				$terms = str_replace( ' ', '%', $options['search'] );
				$search = $domain::getSearch();

				$like = array();
				foreach( $search as $field )
					$like[$field] = $terms;

				$q->like( $like );
			}

			if( ! empty( $options['group'] ) )
				$q->group( $options['group'] );

			$asc = $desc = array();

			if( ! empty( $options['desc'] ) )
			{
				$desc = explode( ',', $options['desc'] );
				$q->order( $desc, 'DESC' );
			}

			if( ! empty( $options['asc'] ) )
			{
				$asc = explode( ',', $options['asc'] );
				$q->order( $asc );
			}

			if( ! empty( $options['fields'] ) )
			{
				$fields = explode( ',', $options['fields'] );
			}

			// this is some pretty crappy hack checking, first run
			$diff = array_diff( array_merge( $asc, $desc ), $fields );
			if( $diff )
				throw new RESTException(
					'Fields not acceptable for ordering: '
						. implode( ', ', $diff ),
					$config->HTTP_NOT_ACCEPTABLE
				);


			if( ! empty( $options['limit'] ) ) {
				if( empty( $options['offset'] ) )
					$options['offset'] = 0;

				if( preg_match( '/\D/', $options['limit'] ) )
					throw new RESTException(
						'Invalid limit: ' . $options['limit'],
						$config->HTTP_NOT_ACCEPTABLE
					);

				if( preg_match( '/\D/', $options['offset'] ) )
					throw new RESTException(
						'Invalid offset: ' . $options['offset'],
						$config->HTTP_NOT_ACCEPTABLE
					);

				$q->limit( $options['limit'], 0 );
			}

		}

		$q->select( $fields, $domain::getTable() )->query();
		$db = \mysql::instance( $config->db[$connection] );
		$db->execute( $q );

		if( $db->stmt->errorCode() === '00000' )
		{
			// check if results need to be grouped (assoc array)
			if( ! empty( $options['index'] ) )
			{
				$data = array();
				$index = explode( ',', $options['index'] );

				// do some n00b hack checking
				$diff = array_diff( $index, $fields );
				if( $diff )
					throw new RESTException(
						'Fields not acceptable for indexing: '
							. implode( ', ', $diff ),
						$config->HTTP_NOT_ACCEPTABLE
					);

				$db->stmt->setFetchMode( \PDO::FETCH_ASSOC );

				// I was hoping to do away with unnecessary "0" indexes (when
				// only one set exists), but consistency is WAY more important
				// i.e., $index[0] = row
				while( $row = $db->next() )
				{
					// unlimited groupability, at the
					// low, low cost of compute cycles :P
					$d =& $data;
					foreach( $index as $g )
						$d =& $d[$row[$g]];
					$d[] = $row;
				}
			}

			// no special grouping, just return all results. consistency is
			// broken here rows are not under sub arrays, so no 0 index :(
			else
				$data = $db->stmt->fetchAll( \PDO::FETCH_ASSOC );

			return static::respond( $data, $status );
		}

		else
		{
			// error code and query are only in response if config::DEV is true
			// @see self::init()
			$info = $db->stmt->errorInfo();
			throw new RESTException(
				'Unable to retrieve data',
				$config->HTTP_BAD_REQUEST,
				$info[1],
				$info[2]
			);
		}

	} // end method collection


	public static function postCollection( array $params )
	{
		global $config;

		if( empty( $params ) )
			throw new RESTException('No data provided',
				$config->HTTP_EXPECTATION_FAILED );

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
		$connection = $domain::getConnection();
		$keys = array_flip( $domain::getKeys( 'primary' ) );

		$insert = array();

		foreach( $params as $k => $p )
		{
			$intersect = array_intersect_key( $p, $keys );

			if( ! $intersect )
			{
				$insert[] = $p;
				unset( $params[$k] );
			}

			else
			{
				array_walk( $intersect, function( $v ) use ( $k, $params )
				{
					if( empty( $v ) )
					{
						$insert[] = $p;
						unset( $params[$k] );
					}
				});

				$params[$k] = array(
					'fields' => array_diff_key( $p, $intersect ),
					'where' => $intersect
				);
			}
		}


		if( $insert )
		{
			$q = new \query;
			$q->insert( 'message_recipients', $insert )->query();
			$db = \mysql::instance( $config->db[$connection] );
			$db->execute( $q );

			if( $db->stmt->errorCode() !== '00000' )
			{
				// @see self::init()
				$info = $db->stmt->errorInfo();
				throw new RESTException(
					'Unable to retrieve data',
					$config->HTTP_BAD_REQUEST,
					$info[1],
					$info[2]
				);
			}
		}

		if( $params )
		{
			$q = new \query;
			foreach( $params as $update )
			{
				$q->update( 'message_recipients', $update['fields'] )
					->where( $update['where'] )->query();
				$db = \mysql::instance( $config->db[$connection] );
				$db->execute( $q );
			}

			if( $db->stmt->errorCode() !== '00000' )
			{
				// @see self::init()
				$info = $db->stmt->errorInfo();
				throw new RESTException(
					'Unable to retrieve data',
					$config->HTTP_BAD_REQUEST,
					$info[1],
					$info[2]
				);
			}
		}

		return static::respond( '', $config->HTTP_OK );
	}
}
?>