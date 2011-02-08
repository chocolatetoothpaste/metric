<?php

/**
 * Description of model
 *
 * @author ross
 */
namespace Service;

abstract class Model
{
	protected $get, $post, $put, $delete;
	static public $method;

	public static function init()
	{
		self::$method	= $_SERVER['REQUEST_METHOD'];
		/*$this->get		= get();
		$this->post		= post();
		$this->put		= put();
		$this->delete	= delete();*/
	}

	protected function parseRange()
	{
		
	}

	/*abstract protected function create();
	abstract protected function read( $id );
	abstract protected function update( $id );
	abstract protected function delete( $id );*/
}

?>
