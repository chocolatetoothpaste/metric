<?php
// DEPRECATE: this code needs to be implemented inline

/**
 * Loops through an array and executes sprintf against each value using $pattern
 * @param string $pattern
 * @param array $array
 * @return array
 */

function asprintf( $pattern, array $array )
{
	$return = array();
	foreach( $array as $k => $v )
	{
		$return[$k] = ( is_array( $v )
			? asprintf( $pattern, $v )
			: sprintf( $pattern, $v ) );
	}
	return $return;
}