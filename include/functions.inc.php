<?php

/**
 * Trims tab characters from a string, handy for slimming down output
 * @param string $text
 */

function trim_tabs( $text )
{
	str_replace( "\t", '', $text );
}


/**
 * Replaces <br /> with a system new line
 * @param string $str
 * @return string
 */

function br2nl( $str )
{
	return preg_replace( '#<br */?>#', "\n", $str );
}


/**
 * Checks if a var is set and has a value
 * @param mixed &$var - the var to check if is set and has value
 * @param mixed $value [optional] - a value to check $var against
 * @param boolean $strict [optional] - T/F strict comparison, default T
 * @return boolean
 */

//function setAndValue( &$var, $value = '', $strict = true )
function setAndValue( &$var, $value = '', $strict = true )
{
	$return = ( !strlen( $value ) ? $var
		: ( $strict ? $var === $value : $var == $value ) );
	return ( $return ? $var : false );

	/**
	 * this is the old method, leaving it here just in case...

	$return = ( isset( $var )
		&& $var
		&& ( ( $strict ? $var === $value : $var == $value )
		|| !strlen( $value ) ) );
	return ( $return ? $var : false );
	 */
}


/**
* Return something based on the condition
* Shortcut for "($condition ? 'here is a string' : '')"
* @param	boolean	$condition
* @param	mixed	$return
* @return	string
*/

function iif( $condition, $return )
{
	return ( $condition ? $return : '' );
}


/**
 * Returns text with a new line inserted
 * @param mixed $text
 * @return string
 */

function br( $text = '' )
{
	return "$text<br />";
}


/**
 * Checks if a secure connection is present, redirects if not
 */

function https()
{
		return ( FORCE_SSL
			? !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on'
			: true );
}


/**
 * Return the value in the array. If $default
 * provided and the key is not set, it is returned.
 * @param  array  &$array  the array
 * @param  string $var     if -1, the array is returned [optional, default -1]
 * @param  mixed  $default [optional, default '']
 * @param  array  $limit   array of values to limit the
 * return to (if the value isn't in the array, the
 * default is returned) [optional, default array()]
 * @return mixed
 */

function arr( &$array, $key = -1, $default = '' )
{
	if( $key === -1 ):
		return $array;
	elseif( isset( $array[$key] ) ):
		return $array[$key];
	else:
		return $default;
	endif;
}


/**
 * Return the GET value, or if not set, the POST value.
 * If $default provided and GET/POST not set, it is returned.
 * @param  string $var if -1, $_GET+$_POST is returned [optional, default -1]
 * @param  mixed  $default [optional, default '']
 * @param  array  $limit array of values to limit the
 * return to (if the value isn't in the array, the
 * default is returned) [optional, default array()]
 * @return mixed
 */

function request( $key = -1, $default = '' )
{
	$array = ( $_GET + $_POST );
	return arr( $array, $key, $default );
}


/**
 * Return the GET value. If $default provided and GET not set, it is returned.
 * @param  string $var     if -1, $_GET is returned [optional, default -1]
 * @param  mixed  $default [optional, default '']
 * @param  array  $limit   array of values to limit the
 * return to (if the value isn't in the array, the
 * default is returned) [optional, default array()]
 * @return mixed
 */

function get( $key = -1, $default = '' )
{
	return arr( $_GET, $key, $default );
}


/**
 * Return the POST value. If $default
 * provided and POST not set, it is returned.
 * @param  string $var     if -1, $_POST is returned [optional, default -1]
 * @param  mixed  $default [optional, default '']
 * @param  array  $limit   array of values to limit the
 * return to (if the value isn't in the array, the
 * default is returned) [optional, default array()]
 * @return mixed
 */

function post( $key = -1, $default = '' )
{
	return arr( $_POST, $key, $default );
}

function put( $key = -1, $default = '', $raw = false )
{
	parse_str(file_get_contents('php://input'), $arguments);
	return arr( $arguments, $key, $default );
}

function delete( $key = -1, $default = '', $raw = false )
{
	parse_str(file_get_contents('php://input'), $arguments);
	return arr( $arguments, $key, $default );
}


/**
 * Return the SESSION value. If $default
 * provided and SESSION not set, it is returned.
 * @param  string $var     if -1, $_SESSION is returned [optional, default -1]
 * @param  mixed  $default [optional, default '']
 * @param  array  $limit   array of values to limit the
 * return to (if the value isn't in the array, the
 * default is returned) [optional, default array()]
 * @return mixed
 */

function session( $key = -1, $default = '' )
{
	return arr( $_SESSION, $key, $default );
}

function server( $key = -1, $default = '' )
{
	return arr( $_SERVER, $key, $default );
}

/**
 * Checks if an array key is set and if it has a value (strict type check)
 * @param array &$array - the array to check for $key in
 * @param string $key - the key to check if has value
 * @param mixed $value [optional] - a value to check $var against
 * @param boolean $strict [optional] - T/F strict comparison, default T
 * @return boolean
 */

function keyAndValue( &$array, $key, $value = '', $strict = true )
{
	$return = false;
	$key = (array)$key;
	if( is_array( $array ) )
	{
		$arr = $array;
			foreach( $key as $k )
				if( isset( $arr[$k] ) )
					$arr = $arr[$k];
				else
					return false;

		if( $arr ){
			if( !strlen( $value ) )
				$return = $arr;
			else
				$return = ( $strict ? $arr === $value : $arr == $value );

		}
	}

	return $return;

	/**
	 * this is the old method, leaving it here just in case...

	$return = ( is_array( $array ) && array_key_exists( $key, $array )
		&& $array[$key]
		&& ( ( $strict ? $array[$key] === $value : $array[$key] == $value )
		|| !strlen( $value ) ) );
	return ( $return ? $array[$key] : false );*/

}


/**
 * Extract the key->value pairs in the given array
 * where the keys are found in the given array
 * @param	array	$array
 * @param	array	$keys
 * @return	array
 */

function array_extract( $keys, $array )
{
	$return = array();
	foreach( $keys as $key ):
		if ( isset( $array[$key] ) )
			$return[$key] = $array[$key];
	endforeach;
	return $return;
}


/**
 * set object properties to values of an array with matching keys
 * @param object $object
 * @param array $array
 */

function array_merge_object( &$object, &$array, $scrub = array() )
{
	foreach( $array as $key => $value )
	{
		//if( property_exists( $object, $key ) && !isset( $scrub[$key] ) )
		if( !empty( $object->$key ) && !isset( $scrub[$key] ) )
			$object->{$key} = $value;
	}
}


/**
 * Flattens a multi-dimensional array (will do it infinitely)
 * @param array $array
 * @return array the flattened array
 */

function array_flatten( $array, $recursive = false )
{
	$return = array();
	foreach( $array as $value)
		if( $recursive && is_array( $value ) )
			$return = array_merge( $return, array_flatten( $value ) );
		else
			$return[] = $value;
	return $return;
}


/**
 * Flattens a multi-dimensional array and flips the keys and values. set strict
 * to false to allow the array to become multi-dimensional again if duplicate
 * key are detected.
 * @param	array	$array	the array to flatten
 * @param	boolean	$strict	flag to trigger strict flattening
 * @return	array	the new flattened, flipped array
 */

function array_flatten_flip( $array, $strict = true )
{
	$return = array();
	foreach( $array as $key => $value )
		if( is_array( $value ) )
			$return = array_merge( $return, array_flatten_flip( $value ) );
		elseif( !$strict && isset( $return[$value] ) )
			$return[$value] = array( $return[$value], $key );
		else
			$return[$value] = $key;
	return $return;
}


/**
 * Detects if an array is multi dimensional
 * @param array $array
 * @return boolean
 */

function is_multi( array &$array )
{
	return ( count( $array ) != count( $array, COUNT_RECURSIVE ) );
}


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

if( !function_exists( 'mime_content_type' ) )
{
	function mime_content_type( $filename )
	{
		if( function_exists( 'finfo_open' ) )
		{
			$finfo = finfo_open( FILEINFO_MIME );
			$mime_type = finfo_file( $finfo, $filename );
			finfo_close( $finfo );
		}
		else
		{
			$types = array(
				'txt' => 'text/plain',
				'htm' => 'text/html',
				'html' => 'text/html',
				'php' => 'text/html',
				'css' => 'text/css',
				'js' => 'application/javascript',
				'json' => 'application/json',
				'xml' => 'application/xml',
				'swf' => 'application/x-shockwave-flash',
				'flv' => 'video/x-flv',

				// images
				'png' => 'image/png',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif',
				'bmp' => 'image/bmp',
				'ico' => 'image/vnd.microsoft.icon',
				'tiff' => 'image/tiff',
				'tif' => 'image/tiff',
				'svg' => 'image/svg+xml',
				'svgz' => 'image/svg+xml',

				// archives
				'zip' => 'application/zip',
				'rar' => 'application/x-rar-compressed',
				'exe' => 'application/x-msdownload',
				'msi' => 'application/x-msdownload',
				'cab' => 'application/vnd.ms-cab-compressed',

				// audio/video
				'mp3' => 'audio/mpeg',
				'qt' => 'video/quicktime',
				'mov' => 'video/quicktime',

				// adobe
				'pdf' => 'application/pdf',
				'psd' => 'image/vnd.adobe.photoshop',
				'ai' => 'application/postscript',
				'eps' => 'application/postscript',
				'ps' => 'application/postscript',

				// ms office
				'doc' => 'application/msword',
				'rtf' => 'application/rtf',
				'xls' => 'application/vnd.ms-excel',
				'ppt' => 'application/vnd.ms-powerpoint',

				// open office
				'odt' => 'application/vnd.oasis.opendocument.text',
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
			);

			$ext = strtolower( trim( strrchr( $filename, '.' ), '.' ) );

			if( isset( $types[$ext] ) )
			{
				$mime_type = $types[$ext];
			}
			else
			{
				$mime_type = 'application/octet-stream';
			}
		}

		return $mime_type;

	}	//	end function mime_content_type

}

function preg_grep_keys( $pattern, $input, $flags = 0 )
{
	$keys = preg_grep( $pattern, array_keys( $input ), $flags );
	$vals = array();
	foreach( $keys as $key )
	{
		$vals[$key] = $input[$key];
	}
	return $vals;
}

function encrypt_password( $password )
{
	return substr( sha1( md5( $password ) ), 5, 32 );
} //	end function __encryption_algorithm


function parseRange( $values )
{
	$ranges = explode(',', $values);
	$ret = array();
	foreach( $ranges as $key => $range )
	{
		if( strpos($range, '-') !== false )
		{
			$range = explode('-', $range);
			if(empty($range[0]))
				$range[0] = '1';

			$range = range($range[0], $range[1]);
			$ret = array_merge($ret,$range);
		}
		else
		{
			$ret[] = intval($range);
		}
	}
	unset( $ranges, $values, $key, $range);
	return $ret;
}


/**
 * Recursively find a key within an array
 * @param	string	$key	the name of the key to find
 * @param	array	$array	the array to search
 */

function array_pluck( $key, &$array )
{
	$array = (array)$array;
	$return = array();
	array_walk_recursive($array, function( &$val, $k ) use( $key, &$return )
	{
		if( $k === $key )
			$return[$val] = 1;
	});
	return $return;
}
?>
