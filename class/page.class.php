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
		else
		{
			if( $this->request === '/js' || $this->request === '/css' )
			{
				$this->request = $request;
				$this->file = PAGE_JS_CSS;
				return;
			}

			// set this to start with, if a match is found this will be changed
			$this->file = PAGE_404;

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

					if( is_array( $action ) ):
						$this->callback = $action;
						$this->file = PAGE_REST_SERVER;
					elseif( is_file( $action ) ):
						$this->file = $action;
					/* this shouldn't be necessary, since the same assignment
						happens before the loop
					else:
						$this->file = PAGE_404;*/
					endif;

					// a match was apparently found, so break the loop
					break;
				}	// end if $matches

			}	// end foreach
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
	 * Get the timestamp of the most recently modified
	 * file (either script or view) and return it
	 * @return int the timestamp
	 */

	public function mtime()
	{
		if( is_array( $this->file ) ):
			$this->time = array_map( 'filemtime', $this->file );
			$this->time = max( $this->time );
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
				die($this->render(require(PAGE_PERMISSION_DENIED)));
			}
			else
				return true;
		}
		else
		{
			self::redirect( 'http://' . server('SERVER_NAME') . '/login' );
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
		$this->body = $body;
		
		if( !$this->template )
		{
			header( 'X-Title: ' . $this->title );
			echo $this->body;
		}
		else
		{
			// using $page instead of $this in template
			// files is a bit less ambiguous
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
