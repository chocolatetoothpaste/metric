<?php

/**
 * Service model determines http method and calls the proper static method.
 * Model is never instantiated, but init is called through child classes.
 */

namespace Service;

abstract class Model
{
	protected static $domain;
	protected $get, $post, $put, $delete;
	static public $method;

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
	}

	public static function getRanges( $ranges )
	{
		//$ranges = static::tokenize( $ranges );
		$return = array();
		foreach( $ranges as $field => $range )
		{
			if( !empty( $range ) )
			{
				if( false !== strpos( $range, ',' ) )
				{
					$return[$field] = \parseRange($range);
				}
				elseif( false !== strpos( $range, '/' ) )
				{
					$return[$field] = "'" . str_replace('/', '\' AND \'', $range) . "'";
				}
				else
				{
					$return[$field] = $range;
				}
			}
		}
		return $return;
	}


	/**
	 * Parse a string (typically the HTTP_PRAGMA header) for options to
	 * manipulate data structure
	 * @param string $options a string to parse for options
	 */

	public static function tokenize( $options )
	{
		$options = preg_split( '/;\s?/', $options );
		$option = array();
		foreach( $options as $opt )
		{
			$opt = explode( '=', $opt );
			$option[$opt[0]] = $opt[1];
		}
		unset( $options, $opt );
		return $option;
	}

	public static function collection( $method, $get = array() )
	{
		// GET is the only method allowed for collections
		if( $method != 'GET' )
			return array( 'success' => 'false', 'status' => HTTP_METHOD_NOT_ALLOWED );

		$domain = static::$domain;
		$fields = $domain::getFields();
		$q = new \query;
		$true_status = HTTP_OK;
		$ranges = static::tokenize( $_SERVER['HTTP_RANGE'] );
		$ranges = array_intersect_key( $ranges, array_flip($fields) );

		if( !empty( $ranges ) )
		{
			$ranges = static::getRanges( $ranges );
			if( isset( $ranges['date'] ) )
			{
				$q->where[] = '`date` BETWEEN ' . $ranges['date'] . '';
			}

			$true_status = HTTP_PARTIAL_CONTENT;
			$q->where = implode(' AND ', $q->where );
		}

		$q->select( $fields, $domain::getTable() );
		$db = \mysql::instance( DB_MAIN );
		$db->quote($q->query);
		$stmt = $db->execute( $q->query );

		if( $stmt )
		{
			if( isset( $_SERVER['HTTP_PRAGMA'] ) )
			{
				$data = array();
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

			$message = array( 'success' => 'true', 'data' => $data, 'status' => $true_status );
		}
		else
			$message = array( 'success' => 'false', 'status' => HTTP_BAD_REQUEST );

		return $message;
	}


	// child classes must define at least these 4 basic methods, even if they
	// only return a 501 (not implemented) message
}

?>
