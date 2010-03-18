<?php
define('START_TIME', microtime(true));

// Absolute file system path to the root
define('SITE_DIR', realpath(dirname(__FILE__)). '/');

// Leave '/' unless this site is in a subfolder ('/subfolder/')
define('SITE_URL', '/tiny/');

//Load the routes class
$routes = load_class('routes');

//Get method
$method = $routes->fetch(1);

// Load the controller (or die on failure) and check for matching method
if(! $controller = load_class($routes->fetch(0), NULL, 'controllers')
	OR !in_array($method, get_class_methods($controller))) {
	die(include(SITE_DIR. 'views/404.php'));
}

// Call the requested method and pass URI segments
call_user_func_array(array(&$controller, $method), array_slice($routes->uri_segments, 2));

// Done!


// Controller class
class controller {

	//Singleton instance object
	public static $instance;
	
	//Set instance
	public function __construct($config=null) {
		//Set singleton instance
		self::$instance =& $this;
	}
	
	/**
	 * This function is used to load views files.
	 *
	 * @param	String	file path/name
	 * @param	array	values to pass to the view
	 * @param	boolean	return the output or print it?
	 * @return	mixed
	 */
	public function view($__file = NULL, $__variables = NULL, $__return = FALSE) {

		if($__variables) {
			// Make each value passed to this view available for use
			foreach($__variables as $key => $variable) {
				$$key = $variable;
			}
		}

		// Delete them now
		$__variables = null;

		// If the file is not found
		if (!file_exists(SITE_DIR. 'views/'. $__file. '.php')) {
			return FALSE;
		}

		// We just want to print to the screen
		if( ! $__return) {
			include(SITE_DIR. 'views/'. $__file. '.php');
			return;
		}
		
		//Buffer the output so we can return it
		ob_start();

		// include theme file
		include(SITE_DIR. 'views/'. $__file. '.php');

		//Get the output
		$buffer = ob_get_contents();
		@ob_end_clean();

		//Return the view
		return $buffer;
	}
	
	//Return this classes instance
	public static function &get_instance() {
		return self::$instance;
	}
}


//Routes class
class routes {

	public $uri_segments			= array('welcome', 'index');
	public $permitted_uri_chars		= 'a-z 0-9~%.:_\-';

	/**
	 * Create a URI string from $_SERVER values
	 */
	public function __construct() {

		//The SERVER values to look for the path info in
		foreach(array('PATH_INFO', 'REQUEST_URI', 'ORIG_PATH_INFO') as $item) {

			//Try the REQUEST_URI
			if(empty($_SERVER[$item])) {
				continue;
			}

			// Remove the start/end slashes
			$string = trim($_SERVER[$item], '\\/');

			//If it is NOT a forward slash
			if(SITE_URL != '/') {
				// Remove the site path -ONLY ONE TIME!
				$string = preg_replace('/^'. preg_quote(trim(SITE_URL, '\\/'), '/'). '(.+)?/i', '', $string, 1);
			}

			//Remove the INDEX.PHP file from url
			$string = str_replace('index.php', '', $string);

			//If anything is left
			if($string) {
				break(1);
			}
		}
		
		//Clean and separate the URI string into an array
		$segments = explode('/', $string);

		foreach($segments as $key => $segment) {

			//Delete Bad Charaters from URI
			$segment = preg_replace('/[^'. preg_quote($this->permitted_uri_chars). ']+/i', '', $segment);

			//If anything is left - add it to our array (allow elements that are ZERO)
			if($segment || $segment === '0') {
				$this->uri_segments[$key] = $segment;
			}
		}
	}

	// Returns the URI array element matching the key
	public function fetch($type=null) {
		//Only return it if it exists
		if (is_int($type) && isset($this->uri_segments[$type])) {
			return $this->uri_segments[$type];
		}
	}
}


/**
* Class registry
*
* This function acts as a singleton.  If the requested class does not
* exist it is instantiated and set to a static variable.  If it has
* previously been instantiated the variable is returned.
*
* @param	string	class name being requested
* @param	mixed	parameters to pass to the class constuctor
* @param	string	folder to look for class in
* @param	bool	optional flag that lets classes get loaded but not instantiated
* @return	mixed
*/
function load_class($class=null, $params=null, $path='models', $instantiate = TRUE) {

	static $objects = array();
	
	//If this class is already loaded
	if(!empty($objects[$class])) {
		return $objects[$class];
	}
	
	// If the class is not already loaded
	if ( ! class_exists($class)) {
	
		// If the requested file does not exist
		if (!file_exists(SITE_DIR. $path . '/'. $class . '.php')) {
			return FALSE;
		}
		
		//Require the file
		require_once(SITE_DIR. $path . '/'. $class . '.php');
		
	}
	
	//If we just want to load the file - nothing more
	if ($instantiate == FALSE) {
		return TRUE;
	}
	
	return $objects[$class] = new $class(($params ? $params : ''));
}
