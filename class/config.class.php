<?php
class config
{
	private $const_ = array();
	private	$var_ = array(
		'classes' => array(), 'alias' => array(), 'routes' => array()
	);

	public function define( $const, $val )
	{
		if( isset( $this->const_[$const] ) )
			throw new Exception("Configuration constant $const already defined");
		else
			$this->const_[$const] = $val;
	}

	public function __get( $var )
	{
		if( isset( $this->const_[$var] ) )
			return $this->const_[$var];
		elseif( isset( $this->var_[$var] ) )
			return $this->var_[$var];
		else
			throw new Exception("Property '$var' not found");
	}

	public function __set( $var, $val )
	{
		if( isset( $this->const_[$var] ) )
			throw new Exception("Configuration constant '$var' already defined");
		elseif( $var == 'const_' || $var == 'prop_' )
			throw new Exception("Unable to override internal container '$var'");
		else
			$this->var_[$var] = $val;
	}

    public function __isset($var)
    {
        return isset($this->const_[$var]) || isset($this->var_[$var]);
    }

    public function __unset($var)
    {
        if( isset( $this->const_[$var] ) )
			throw new Exception("Unable to unset configuration constant $var");
		elseif( isset( $this->var_[$var] ) )
			unset( $this->var_[$var] );
		else
			throw new Exception("Illegal double free on '$var'");
    }
}
?>