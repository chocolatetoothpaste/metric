<?php

/**
 * @package page_controller
 */
namespace metric\page;

abstract class page
{

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
	public $content_type = 'text/html; charset=utf-8';
	public $body;
	public $hash;
	public $https;
	public $private = false;

	private $cache = false;

	function __construct()
	{
		global $config;
		$this->https = ( $config->FORCE_SSL
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
		$this->request = $request;

		// check if the page is in the public dir, protected pages dir, or if
		// a "view" exists. finally, check if request is defined in config file
		// as a service or alias to a file
		if( file_exists( $config->PATH_CONTROLLER . $this->request . '.php' ) )
			$this->file = $config->PATH_CONTROLLER . $this->request . '.php';
		elseif( !empty( $config->alias[$this->request] ) )
			$this->file = $config->alias[$this->request];
		else
		{
			// set this to start with, if a match is found this will be changed
			// otherwise it defaults to error page (which is a good thing)
			$this->file = $config->PAGE_404;

			/**
			 * grab all the services registered and combine them into a single
			 * string so the URL matching is only executed once. Do regex
			 * substitutions for extracting named params and the service name.
			 * The (?J) modifier allows duplicate named params, since multiple
			 * routes will have "id" or other fields that are the same
			 */
			$string = '(?J)^' . implode( '$|^', $config->routes ) . '$';
			$match = array(
				'#/:([\w]+)#',
				'#/@(\w+)#',
				'#/%(\w+)#'
			);

			$replace = array(
				'/(?<service>${1})',
				'/(?<${1}>[@\w]+)',
				'/?(?<${1}>[%\w]+)*'
			);

			$pattern = preg_replace( $match, $replace, $string );
			$pattern = "#{$pattern}#";

			preg_match_all( $pattern, $this->request,
				$matches, PREG_SET_ORDER );

			if( $matches ):
				// flatten the array
				$matches = $matches[0];

				// this is a REALLY shitty way to do this, but at the time of
				// writing, there is no alternative. hopefully a flag or
				// modifier will be introduced in the future
				array_walk( $matches, function( $v, $k ) use( &$matches )
				{
					if( !$v || is_numeric( $k ) )
						unset($matches[$k]);
				});
				//*/

				$this->file = $config->PAGE_REST_SERVER;
				$service = 'Service\\' . ucwords($matches['service']);

				unset($matches['service']);

				$this->params = $matches;
				if( !empty( $config->classes[$service] ) )
					$this->callback = array($service, 'init');

			endif;

		}

		if( !file_exists( $this->file ) )
			$this->file = $config->PAGE_404;

		// check if there is a view associated with the page
		$path = pathinfo( $this->file );
		$path['dirname'] = str_replace( $config->PATH_CONTROLLER,
			$config->PATH_VIEW, $path['dirname'] );
		$path = "$path[dirname]/$path[filename].phtml";
		$this->view = ( file_exists( $path ) ? $path : null );
		unset($path);

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
			global $config;
			require( $config->PATH_TEMPLATE . "/$this->template" );

			// cache the page if the stars are aligned (no errors),
			// because caching an errored page would be stupid
			if( $this->cache && strlen( $this->body ) && !error_get_last() )
			{
				$date = strtotime( '+1 month' );
				$date = gmdate( DATE_RFC1123, $date );
				header( "Expires: {$date}" );

				if( is_writable( $config->PATH_CACHE ) )
					file_put_contents( $config->PATH_CACHE . "/{$this->hash}",
						ob_get_contents(), LOCK_EX );
			}

			// display page, even if it doesn't get cached
			ob_end_flush();
		}
	}	// end method render


	/**
	 * Caches the output of a page
	 */

	public function cache( $request = null, $unique_id = 0 )
	{
		global $config;
		$request = iif( !$request, $this->request );
		// grab the most recent mtime of a file/files, create a hash
		$mtime = max( filemtime( $this->file ), filemtime( $this->view ) );

		$this->hash = md5( $request ) . "-{$unique_id}-{$mtime}";
		$cache_file = $config->PATH_CACHE . "/{$this->hash}";
		$visibility = ( $this->private ? 'private' : 'public' );
		header( "Cache-Control: {$visibility}", false );
		header( "Etag: {$this->hash}" );
		header( 'Pragma: cache' );

		// check if user has a local cached file
		// else check for a server cached file
		// else generate a new file and if possible cache it
		if( !empty( $_SERVER['HTTP_IF_NONE_MATCH'] )
			&& $_SERVER['HTTP_IF_NONE_MATCH'] === "$this->hash" )
		{
			global $__http_status;
			header( $__http_status[$config->HTTP_NOT_MODIFIED] );
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
	 * Redirects to another page, safe fall back if headers are sent
	 * @access public static
	 * @param	string	$url
	 */

	public static function redirect( $url )
	{
		global $config;
		// make ABSOLUTELY sure that the session is written to the disk!
		session_write_close();

		if( $config->DIE_BEFORE_REDIRECT )
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
	 * @param	string	$clip_name
	 */

	public function loadClip( $clip, $return = false )
	{
		global $config;
		$clip = $config->PATH_CLIP . "/{$clip}.php";
		if( file_exists( $clip ) )
		{
			if( $return )
				return include( $clip );
			else
				include( $clip );
		}
	}

}	// end class page

?>