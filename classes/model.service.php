<?php

/**
 * Description of model
 *
 * @author ross
 */
namespace Service;

abstract class Model
{
	public static function init( $id, $method = 'GET', $data = array() )
	{
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

	protected $get, $post, $put, $delete;
	static public $method;

	protected function parseRange()
	{
		
	}

	abstract protected static function create( $post );
	abstract protected static function read( $id );
	abstract protected static function update( $id, $put );
	abstract protected static function delete( $id );
}

?>
