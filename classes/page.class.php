<?php

/**
 * @package page_controller
 */

class page
{
	/**
	 * If set to false, sends raw content. If set to string, encapsulates
	 * content in a template loaded from the string provided.
	 * @var mixed $template
	 */

	public $template = false;
	public $js = array();
	public $css = array();
	public $file;
	public $callback;
	public $view;
	public $title;
	public $params = array();
	public $uid;
	public $mtime = 0;
	public $authorized = false;
	public $request;
	public $cache = false;
	public $content_type = 'text/html; charset=utf-8';
	public $body;
	public $response;
	public $headers;


	/**
	 * Parses $request and tries to find a matching page/script. Looks
	 * at public files as well as files protected below site root.
	 * @param string $request
	 */

	public function parseURL( $request )
	{
		// trim query string from the request
		$this->request = strtok( $request, '?' );

		// check if the page is in the public dir, elseif check if page is in
		// protected pages dir, else check if request is defined in config file
		if( is_file( PATH_HTDOCS . $this->request ) )
			$this->file = PATH_HTDOCS . $this->request;
		elseif( file_exists( PATH_PAGE . $this->request . '.php' ) )
			$this->file = PATH_PAGE . $this->request . '.php';
		else
		{
			global $__urls;

			// loop through the urls in the main config file and find any that match
			// the page request, return filename and register page params
			foreach( $__urls as $url => $action )
			{
				$regex = array(
					'match' => array(
						'#/\*#',		// wildcard match
						'#/@[\w]+#',	// required params
						'#/%[\w]+#'		// optional params
					),
					'replace' => array(
						'/([^/].*)',
						'/([^/]*)',
						'/?([^/]*)'

					)
				);

				// TODO: figure out a way to cache the urls once params have been replaced
				$pattern = preg_replace( $regex['match'], $regex['replace'], $url );
				preg_match_all( "#^{$pattern}$#", $this->request, $matches, PREG_SET_ORDER );

				// if any urls in config match the page request, set the filename or
				// method call and set any params passed in the url
				if( $matches )
				{
					preg_match_all('/\/[@|%]([\w]+)/', $url, $params );
					array_shift( $params );
					array_shift( $matches[0] );

					if( $params[0] && $matches[0] ):
						$this->params = array_combine( $params[0], $matches[0] );
					endif;

					if( is_array( $action ) ):
						$this->callback = $action;
						$this->file = PATH_LIB . '/rest.php';
					elseif( is_file( $action ) ):
						$this->file = $action;
					else:
						$this->file = PAGE_404;
					endif;

					// a match was apparently found, so break the loop
					break;
				}	// end if $matches

			}	// end foreach

			if( !$matches )
				$this->file = PAGE_404;
		}	// end main if statement

		// check for a view associated with the page that's found
		$path = pathinfo( $this->file );
		$path['dirname'] = str_replace( PATH_PAGE, PATH_VIEW, $path['dirname'] );
		$this->view = "$path[dirname]/$path[filename].html";

		if( !file_exists( $this->view ) )
			$this->view = null;

		// set the "last modified" time of the file or view for cache verifications


	}	// end method parseURL


	/**
	 * Get the timestamp of the most recently modified file (either script or view) and return it
	 * @return int the timestamp
	 */

	public function mtime()
	{
		if( empty( $this->mtime ) )
			$this->mtime = max( filemtime( $this->view ), filemtime( $this->file ) );

		return $this->mtime;
	}


	/**
	 * Get the request headers passed by the client
	 * @return array all headers received by the page
	 */

	public function headers()
	{
		if( empty( $this->headers ) )
		{
			$this->headers = apache_request_headers();
		}

		return $this->headers;
	}

	
	/**
	 * Parses a string (page body) and looks for custom template tags
	 * @param string $string
	 * @return $return
	 */

	public function parseTags( $string )
	{

		// had to put a space between the last '*' and '/' because it was breaking comments
		$pattern = '#\<titan:(@|\#|\w*)([\w\._]*)[^\>]*/?>#';
		preg_match_all($pattern, $string, $tags, PREG_SET_ORDER);

		foreach( $tags as $t )
		{
			if( $t[1] == '@' )
			{
				if( strpos( $t[2], '.' ) )
				{
					$var = explode( '.', $t[2] );
					global ${$var[0]};
					if( is_object( ${$var[0]} ) && property_exists( ${$var[0]}, $var[1] ) )
					{
						$string = str_replace( $t[0], ${$var[0]}->$var[1], $string );
					}
					else
					{
						$string = str_replace( $t[0], '', $string );
					}
				}
			}
			elseif( $t[1] == 'form' )
			{
				$action = '';
				$replace = preg_replace( array( '#titan:#', '#action="(\w*)"#' ), array( '', "action=\"$action/$1\"" ), $t[0]);
				$string = str_replace( $t[0], $replace, $string );
				$string = str_replace( '</titan:form>', '</form>', $string);
			}
			elseif( $t[1] == 'input' )
			{
				$p = '#value=@([\w\._]*)#';
				preg_match_all( $p, $t[0], $m, PREG_SET_ORDER );

				if( strpos( $m[0][1], '.' ) )
				{
					$vars = explode( '.', $m[0][1] );
					list( $class, $var ) = $vars;
					global ${$class};
					$var =& ${$class}->$var;
				}
				else
				{
					$var = $m[0][1];
					global ${$var};
					$var = $$var;
				}

				$replace = preg_replace( array( '#titan:#', '#@[\w\._]*#' ), array( '', "\"$var\"" ), $t[0] );
				$string = str_replace( $t[0], $replace, $string );
			}
		}

		/*//
		die( $string );
		/*/
		return $string;
		//*/

	} // end function parseTags


	/**
	 * Makes a reqeust to a URL and returns the output.
	 * @param string $url
	 * @param string $method
	 * @param array $params
	 * @return string
	 */

	public function request( $url, $method = 'GET', $content = array(), $headers = array() )
	{
		$stream_headers = '';
		foreach( $headers as $header => $value )
		{
			$stream_headers .= "$header: $value";
		}

		$content = http_build_query( $content );

		if( $method == 'GET' && $content )
		{
			$url .= "?$content";
			$content = array();
		}

		$length = strlen( $content );
		$header = "Content-Type: application/x-www-form-urlencoded\r\nContent-Lenght: $length\r\n"
			. 'Connection: close' . "\r\n"
			. 'Date: ' . gmdate('r') . "\r\n"
			. $stream_headers;

		$opts = array(
			'http' => array(
				'protocol_version' => '1.1',
				'method'	=>	$method,
				'header'	=>	$header,
				'content'	=>	$content
			)
		);

		$context = stream_context_create( $opts );
		$this->response = file_get_contents( $url, false, $context );

		return $this->response;
	}



	/**
	 * checks if the user has permission to view the page requested
	 * @global object $auth
	 * @param string $code
	 */

	public function authenticate( $bit = 0, $permission = '' )
	{
		if( https() && keyAndValue( $_SESSION, 'user' ) instanceof User )
		{
			if( !$_SESSION['user']->authenticate( $bit, $permission ) )
				die( $this->loadTag( 'permission_error', true ) );
			else
				return true;
		}
		else
		{
			self::redirect( 'https://' . server('SERVER_NAME') . '/login' );
		}
	}


	/**
	 * Redirects to another page, safe fall back if headers are sent
	 * @access public static
	 * @param	string	$url
	 */

	public static function redirect( $url )
	{
		// make ABSOLUTELY sure that the session is written to the disk!
		session_write_close();

		if( DIE_BEFORE_REDIRECT )
			die( 'Dying before redirect, <a href="' . $url
				. '">click here</a> to continue.' );

		if( !headers_sent() )
		{
			header( "Location: $url" );
		}
		else
		{
			?>
			<script type="text/javascript">
				document.location.replace('<?php echo addslashes( $url );?>');
			</script>
			<?php
		} // else headers sent
		die( 'Redirecting... <a href="' . $url
			. '">click here</a> if redirect fails.');
	}	// end function redirect


	/**
	 * Sets up the page and displays it
	 * @param string $contents
	 * @global object $auth
	 */

	public function render( $body = '' )
	{
		if( !$this->template )
		{
			header( 'X-Title: ' . $this->title );
			echo $body;
		}
		else
		{
			// using $page instead of $this in template files is a bit less ambiguous
			$page =& $this;
			require( PATH_TEMPLATE . "/$this->template" );
		}
	}	// end method render


	/**
	 * Loads a predefined tag
	 * @param	string	$tag_name
	 */

	public function loadTag( $tag, $return = false )
	{
		$tag = PATH_TAG . "/{$tag}.php";
		if( file_exists( $tag ) )
		{
			if( $return )
			{
				return include( $tag );
			}
			else
			{
				include( $tag );
			}
		}
	} // end function loadTag

}	// end class page

?>
