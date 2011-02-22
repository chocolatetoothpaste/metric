<?php

/**
 * Service model determines http method and calls the proper static method.
 * Model is never instantiated, but init is called through child classes.
 */

namespace Service;

abstract class Model
{
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
		$ranges = preg_split('/;\s?/', $ranges);
		$range = array();
		foreach( $ranges as $ranges )
		{
			$pair = explode('=', $ranges);
			if( false === strpos( $pair[1], '/' ) )
			{
				$range[$pair[0]] = \parseRange($pair[1]);
			}
			else
			{
				$range[$pair[0]] = "'" . str_replace('/', '\' AND \'', $pair[1]) . "'";
			}
		}
		return $range;
	}

	public static function collection( $method, $get = array() )
	{
		// GET is the only method allowed for collections
		if( $method != 'GET' )
			return array( 'success' => 'false', 'status' => HTTP_METHOD_NOT_ALLOWED );

		return static::coll();

	}

	protected $get, $post, $put, $delete;
	static public $method;

	// child classes must define at least these 4 basic methods, even if they
	// only return a 501 (not implemented) message
	abstract protected static function create( $post );
	abstract protected static function read( $id );
	abstract protected static function update( $id, $put );
	abstract protected static function delete( $id );
}

?>
