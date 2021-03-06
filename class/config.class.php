<?php
namespace Metric;

class config
{
	private $const_ = array(), $var_ = array('classes' => array(),
		'alias' => array(), 'routes' => array(), 'js' => array(),
		'css' => array() );


	/**
	 * Define a config var as a psuedo-constant
	 * @param string $const
	 * @param string $val
	 */

	public function define( $const, $val = '' )
	{

		if( is_array( $const ) )
		{
			$int = array_intersect_key( $this->const_, $const );
			if( ! $int )
				$this->const_ = array_merge( $this->const_, $const );
			else
				throw new \Exception( 'Cannot redeclare config constants'
					. implode( ', ', $const ) );
		}

		else
		{
			if( ! isset( $this->const_[$const] ) )
				$this->const_[$const] = $val;
			else
				throw new \Exception( "Cannot redeclare config constant $const" );

		}
	}


	/**
	 * Get the value of a constant or variable
	 * @param string $var
	 * @return string
	 */

	public function __get( $var )
	{
		if( isset( $this->const_[$var] ) )
			return $this->const_[$var];
		elseif( isset( $this->var_[$var] ) )
			return $this->var_[$var];
		else
			throw new \Exception( "Property '$var' not found" );
	}


	/**
	 * Set a config variable if config constant of the same name does not exist
	 * @param string $var
	 * @param string $val
	 */

	public function __set( $var, $val = '' )
	{
		if( isset( $this->const_[$var] ) )
			throw new \Exception( "Configuration constant $var already defined" );
		else
			$this->var_[$var] = $val;
	}


	/**
	 * Check if a config constant or variable is set
	 * @param string $var
	 * @return bool
	 */

    public function __isset( $var )
    {
        return isset( $this->const_[$var] ) || isset( $this->var_[$var] );
    }


    /**
     * Unset a config var
     * @param string $var
     */

    public function __unset( $var )
    {
        if( isset( $this->const_[$var] ) )
			throw new \Exception( "Cannot unset configuration constants: $var" );
		elseif( isset( $this->var_[$var] ) )
			unset( $this->var_[$var] );
		else
			throw new \Exception( "Illegal double free on '$var'" );
    }
}
?>
