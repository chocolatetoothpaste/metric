<?php
namespace Metric\Page;

abstract class Page
{
	public $template = false;
	public $file;
	public $view;
	public $js = array(), $css = array();
	public $params = array();
	public $request;
	public $callback;
	public $content_type = 'text/html; charset=utf-8';
	public $body;
	public $hash;
	public $https;
	public $visibility = 'public';

	private $cache = false;
	private $cached;

	function __construct()
	{
		global $config;
		$this->https = ( $config->DEV
			? true
			: ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) );
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
		if( ! empty( $config->PATH_CONTROLLER ) && file_exists( $config->PATH_CONTROLLER . $this->request . '.php' ) )
			$this->file = $config->PATH_CONTROLLER . $this->request . '.php';
		elseif( !empty( $config->alias[$this->request] ) )
			$this->file = $config->alias[$this->request];
		else//if( false !== strpos($request, $config->URL_REST, 0) )
		{
			/**
			 * grab all routes and combine them into a single pattern
			 * so the URL matching is only executed once. Do regex
			 * substitutions for extracting named params and the service name.
			 * The (?J) modifier allows duplicate named params, since multiple
			 * routes will accept params with overlapping names
			 */
			$string = '(?J)^' . implode( '$|^', $config->routes ) . '$';
			$match = array(
				'#/:([\w]+)#',
				'#/@(\w+)#',
				'#/%(\w+)#'
			);

			$replace = array(
				'/(?P<service>${1})',
				'/(?P<${1}>[\w]+)',
				'/?(?P<${1}>[\w]+)*'
			);

			$pattern = preg_replace( $match, $replace, $string );
			$pattern = "#{$pattern}#";

			preg_match_all( $pattern, $this->request,
				$matches, PREG_SET_ORDER );

			if( $matches )
			{
				// shift a useless index
				$matches = $matches[0];

				/// this is a REALLY shitty way to do this, but at the time of
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

				return;
			}
		}

		if( !file_exists( $this->file ) )
		{
			if( !empty( $config->PAGE_404 ) && file_exists( $config->PAGE_404 ) )
				$this->file = $config->PAGE_404;
			else
			{
				header('HTTP/1.0 404 Not Found');
				echo '<html><body><h1>404 Not Found</h1></body></html>',
					'The page "', $this->request, '" could not be found.';
				die;
			}
		}
		else
		{
			// check if there is a view associated with the page
			$path = pathinfo( $this->file );
			$path['dirname'] = str_replace( $config->PATH_CONTROLLER,
				$config->PATH_VIEW, $path['dirname'] );
			$path = "$path[dirname]/$path[filename].phtml";
			$this->view = ( file_exists( $path ) ? $path : null );
			unset($path);
		}
	}	// end method parseURL


	/**
	 * Load page specific scripts passed as function args
	 */

	public function js()
	{
		$this->js = array_merge( func_get_args(), $this->js );
	}


	/**
	 * Load page specific styles passed as function args
	 */

	public function css()
	{
		$this->css = array_merge( func_get_args(), $this->css );
	}


	/**
	 * Sets up the page and displays it
	 * @param string $contents
	 * @global object $auth
	 */

	public function render()
	{
		// in the past, there have been a couple (minor) issues with the
		// browser trying to download the page rather than render it,
		// this header should fix this.
		header( "Content-Type: {$this->content_type}" );

		if( !$this->template )
			echo $this->body;
		else
		{
			// a lot of pages/templates will use config vars,
			// so $config should be pulled into scope
			global $config;
			require( $this->template );

			// cache the page if the stars are aligned (no errors),
			// because caching an errored page would be stupid
			if( $this->cache && strlen( $this->body ) && !error_get_last() )
			{
				$date = strtotime( '+1 month' );
				$date = gmdate( DATE_RFC1123, $date );
				header( "Expires: {$date}" );

				// check if the cache directory is writable
				// and attempt to cache page output
				if( is_writable( dirname( $this->cached ) ) )
					file_put_contents( $this->cached,
						ob_get_contents(), LOCK_EX );
			}

			// display page, even if it doesn't get cached
			ob_end_flush();
		}
	}	// end method render


	/**
	 * Caches the output of a page
	 */

	public function cache( $request = null )
	{
		global $config;
		$request = iif( !$request, $this->request );
		// grab the most recent mtime of a file/files, create a hash
		$mtime = max( filemtime( $this->file ), filemtime( $this->view ) );

		$this->hash = md5( $request ) . "-{$mtime}";
		$this->cached = $config->PATH_CACHE . "/{$this->hash}";
		header( "Cache-Control: {$this->visibility}", false );
		header( "Etag: {$this->hash}" );
		header( 'Pragma: cache' );

		// check if user has a local cached file
		// else check for a server cached file
		// else generate a new file and if possible cache it
		if( !empty( $_SERVER['HTTP_IF_NONE_MATCH'] )
			&& $_SERVER['HTTP_IF_NONE_MATCH'] === "$this->hash" )
		{
			header( $config->http_status[$config->HTTP_NOT_MODIFIED] );
			die;
		}
		elseif( file_exists( $this->cached ) && filesize( $this->cached ) > 0 )
		{
			header( "X-Cache-Retrieved: {$this->hash}" );
			echo file_get_contents( $this->cached );
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
		// write session to the disk
		session_write_close();

		// option to die before redirecting the page, useful when debugging
		// @see config.inc.php
		if( $config->DIE_BEFORE_REDIRECT )
			die( 'Dying before redirect, <a href="' . $url
				. '">click here</a> to continue.' );

		if( !headers_sent() )
			header( "Location: $url" );
		else
		{
			echo '
			<script type="text/javascript">
				document.location.replace(', addslashes( $url ), ');
			</script>';
		}

		die( 'Redirecting... <a href="' . $url
			. '">click here</a> if redirect fails.' );
	}	// end function redirect


	/**
	 * Detect if the page is using HTTPS protocol and return bool or redirect
	 */

	public static function https( $redirect = true )
	{
		global $config;
		$https = false;
		$https = ( $config->DEV && ! $config->FORCE_SSL
			? true
			: !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' );

		if( $redirect && !$https )
		{
			static::redirect( 'https://' . $_SERVER['HTTP_HOST']
				. $_SERVER['REQUEST_URI'] );
			die;
		}
		else
			return $https;
	}


	/**
	 * Loads a HTML fragment
	 * @param	string	$frag
	 * @param	boolean	$return
	 */

	public function frag( $frag, $return = false )
	{
		global $config;
		$frag = $config->PATH_FRAG . "/{$frag}.phtml";
		if( file_exists( $frag ) )
		{
			if( $return )
				return include( $frag );
			else
				include( $frag );
		}
	}

}	// end class page

?>