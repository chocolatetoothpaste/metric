<?php
class config
{
	private $constants = array();
	public $classes = array(), $alias = array(), $routes = array();

	public function define( $constant, $value )
	{
		if( empty( $this->constants[$constant] ) )
			$this->constants[$constant] = $value;
		else
			throw new Exception("Configuration constant $constant already defined");
	}

	public function __get( $constant )
	{
		if( isset( $this->constants[$constant] ) )
			return $this->constants[$constant];
		else
			throw new Exception("Property '$constant' not found");
	}

	public function __set( $var, $value )
	{
		if( isset( $this->constants[$var] ) )
			throw new Exception("Configuration constant '$var' already defined");
		else
			$this->$var = $value;
	}
}
?>