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
	public $view;
	public $title;
	public $params = array();
	public $mtime = 0;
	public $request;
	public $callback;
	public $cache = false;
	public $content_type = 'text/html; charset=utf-8';
	public $body;
	public $hash;
	public $https;


	function __construct()
	{
		$this->https = ( FORCE_SSL
			? !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on'
			: true );
	}


	/**
	 * Parses $request and tries to find a matching page/script. Looks
	 * at public files as well as files protected below site root.
	 * @param string $request
	 */

	public function parseURL( $request )
	{
		global $config;
		// trim query string from the request
		$this->request = strtok( $request, '?' );

		// check if the page is in the public dir, protected pages dir, or if
		// a "view" exists. finally, check if request is defined in config file
		if( is_file( PATH_HTDOCS . $this->request ) )
			$this->file = PATH_HTDOCS . $this->request;
		elseif( file_exists( PATH_CONTROLLER . $this->request . '.php' ) )
			$this->file = PATH_CONTROLLER . $this->request . '.php';
		elseif( file_exists( PATH_VIEW . $this->request . '.phtml' ) )
			$this->file = PATH_VIEW . $this->request . '.phtml';
		elseif( !empty( $config->redirect[$this->request] ) )
			$this->file = $config->redirect[$this->request];
		else
		{
			// set this to start with, if a match is found this will be changed
			// otherwise it defaults to error page (which is a good thing)
			$this->file = PAGE_404;

			///
			// grab all the services registered and combine them into a single
			// string so the URL matching is only executed once. Do regex
			// substitutions for extracting named params and the service name.
			// The (?J) modifier allows duplicate named params
			$string = '(?J)^' . implode( '$|^', $config->services ) . '$';
			$match		= array( '#/:([\w]+)#',
				'#/@(\w+)#', '#/%(\w+)#' );
			$replace	= array( '/(?P<service>${1})',
				'/(?P<${1}>[@\w]+)', '/?(?P<${1}>[%\w]+)*' );

			$pattern = preg_replace( $match, $replace, $string );
			$pattern = "#{$pattern}#";
	
			preg_match_all( $pattern, $this->request,
				$matches, PREG_SET_ORDER );

			if( $matches ):
				// flatten the array
				$matches = $matches[0];

				// strip out numeric keys, the server only wants named params
				array_walk( $matches, function( $v, $k ) use( &$matches )
				{
					if( !$v || is_numeric( $k ) )
						unset($matches[$k]);
				});

				$this->file = PAGE_REST_SERVER;
				$service = 'Service\\' . ucwords($matches['service']);

				unset($matches['service']);

				$this->params = $matches;
				if( !empty( $config->classes[$service] ) )
					$this->callback = array($service, 'init');

			endif;
			/*/
			// loop through defined urls (aliases) and find any that match
			// the page request, return filename and set page params
			foreach( $config->urls as $url => $action )
			{
				// matches:			 required,		optional
				$match		= array( '#/@[\w]+#',	'#/%[\w]+#' );
				$replace	= array( '/([^/]*)',	'/?([^/]*)' );

				// TODO: figure out a way to cache the
				// urls once params have been replaced
				$pattern = preg_replace( $match, $replace, $url );
				preg_match_all( "#^{$pattern}$#", $this->request,
					$matches, PREG_SET_ORDER );

				// if any urls in config match the page request, set the
				// filename or method call and set any params passed in the url
				if( $matches )
				{
					preg_match_all('/\/[@|%]([\w]+)/', $url, $params );
					array_shift( $params );
					array_shift( $matches[0] );

					if( $params[0] && $matches[0] ):
						$this->params =
							array_combine( $params[0], $matches[0] );
					endif;
					unset($this->params['id']);

					if( is_array( $action ) ):
						$this->callback = $action;
						$this->file = PAGE_REST_SERVER;
					elseif( is_file( $action ) ):
						$this->file = $action;
					endif;
error_log(print_r($this->params, true));
					// a match was apparently found, so break the loop
					break;
				}	// end if $matches

			} //end foreach
			//*/
		}

		// check for a view for the page
		$path = pathinfo( $this->file );
		$path['dirname'] =
			str_replace( PATH_CONTROLLER, PATH_VIEW, $path['dirname'] );
		$this->view = "$path[dirname]/$path[filename].phtml";

		if( !file_exists( $this->view ) || $this->view === $this->file )
			$this->view = null;

	}	// end method parseURL


	/**
	 * Sets up the page and displays it
	 * @param string $contents
	 * @global object $auth
	 */

	public function render()
	{
		header( "Content-Type: {$this->content_type}" );
		
		if( !$this->template )
		{
			echo $this->body;
		}
		else
		{
			require( PATH_TEMPLATE . "/$this->template" );

			// cache the page if the stars are aligned (no errors),
			// because caching an errored page would be stupid
			if( $this->cache && strlen( $this->body ) && !error_get_last() )
			{
				$date = strtotime( '+1 month' );
				$date = gmdate( DATE_RFC1123, $date );
				header( "Expires: {$date}" );

				if( is_writable( PATH_CACHE ) )
					file_put_contents( PATH_CACHE . "/{$this->hash}",
						ob_get_contents(), LOCK_EX );
			}

			// the page is displayed whether it's cached or not, so flush the buffer
			ob_end_flush();
		}
	}	// end method render


	/**
	 * Caches the output of a page
	 */

	public function cache( $request = null, $unique_id = 0 )
	{
		$request = iif( !$request, $this->request );
		$this->hash = md5( $request ) . "-{$unique_id}-{$this->mtime}";
		$cache_file = PATH_CACHE . "/{$this->hash}";
		header( "Etag: {$this->hash}" );
		header( 'Pragma: cache' );

		// check if user has a local cached file
		// else check for a server cached file
		// else generate a new file and if possible cache it
		if( !empty( $_SERVER['HTTP_IF_NONE_MATCH'] )
			&& $_SERVER['HTTP_IF_NONE_MATCH'] === "$this->hash" )
		{
			global $__http_status;
			header( $__http_status[HTTP_NOT_MODIFIED] );
			die;
		}
		elseif( file_exists( $cache_file ) && filesize( $cache_file ) > 0 )
		{
			header( "X-Cache-Retrieved: {$this->hash}" );
			echo file_get_contents( $cache_file );
			die;
		}
		else
		{
			$this->cache = true;
			ob_start();
		}
	}


	/**
	 * Get the timestamp of the most recently modified
	 * file (either script or view) and return it
	 * @return int the timestamp
	 */

	public function mtime()
	{
		if( is_array( $this->file ) ):
			$this->mtime = array_map( 'filemtime', $this->file );
			$this->mtime = max( $this->mtime );
		else:
			$this->mtime =
				max( filemtime( $this->view ), filemtime( $this->file ) );
		endif;

		return $this->mtime;
	}


	/**
	 * checks if the user has permission to view the page requested
	 * @global object $auth
	 * @param string $code
	 */

	public function authenticate( $bit = 0, $permission = '' )
	{
		//if( $this->https && keyAndValue( $_SESSION, 'user' ) instanceof User )
		if( keyAndValue( $_SESSION, 'user' ) instanceof \Domain\User )
		{
			if( !$_SESSION['user']->authenticate( $bit, $permission ) )
			{
				$this->file = PAGE_PERMISSION_DENIED;
				die($this->render());
			}
			else
				return true;
		}
		else
		{
			self::redirect( 'http://' . getenv('SERVER_NAME') . '/login' );
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
			echo '
			<script type="text/javascript">
				document.location.replace(', addslashes( $url ), ');
			</script>';
		} // else headers sent
		die( 'Redirecting... <a href="' . $url
			. '">click here</a> if redirect fails.');
	}	// end function redirect


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
