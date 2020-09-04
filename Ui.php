<?php namespace ProcessWire;
/**
 * Class UI is the base class for all user interfaces. This file is the equivalent of the "controller" in traditional MVC (Model-View-Controller) lingo.
 *
 * A "user interface" in our case represents any collection of php logic, markup/html, css, and javascript used in generating the graphical user interface (GUI) or portion of the GUI that the end user interacts with.
 *
 * Classes extending the UI class should contain all logic for handling user input (http requests) and preparing data for output.
 *
 * UIs make use of a separate "view" file for html markup (TemplateName.php by default), which uses ProcessWire's native TemplateFile class. Only presentational logic should be contained in the view file. (e.g. if/thens for showing/hiding content and simple loops for lists and tables)
 *
 * Business logic should be handled by the "model", which in this case is represented by ProcessWire Pages. To add custom properties and methods to a page, the Page class should be extended using a custom module or added to a /site/templates/model folder.
 *
 * All css, js, and html for an interface are grouped together in a directory with the same name in the templates folder. (i.e. "feature folders")
 *
 * Interfaces are nestable (also known as HMVC), meaning any interface can call/instantiate another interface and assign its output to a variable which can be placed within its (the parent interface's) view. This allows for a modular architecture where each user interface "widget" can have its own controller, view, js, and css files.
 *
 *
 * Framework by Michael J Spooner for Solution Innovators (2015)
 *
 */
abstract class Ui extends Wire {

	/**
	 * Configurable properties included on each block - Feel free to set/override these in your UI subclass
	 */
	public $id; // A unique identifier for the particular instance of the UI (useful mainly for AJAX calls)
	public $version; // Update the version number of the UI to force browsers to reload cached js & css assets
	public $headScripts = [];
	public $footScripts = [];
	public $styles = [];
	public $minify = true;
	public $wrapper = true; // Enable/disable the header and footer markup surrounding the block
	public $wrapperAttributes = []; // Associative array of attributes to add to the wrapper div
	public $classes = ''; // Classes to add to the wrapper todo: Change to wrapperClasses?
	public $debug = null; // Allows AJAX data to be output even if $config->ajax is false. May be used to switch on other debug data as needed. If not set, the value from PW's global $config->debug will be used


	/**
	 * Reserved properties, managed internally - Don't set manually
	 */
	protected $view; // The view file (HTML markup/template) this user interface will use for output
	protected $ajax = []; // An ajax data array to output instead of the view for ajax requests
	public $path; // Holds the directory path to this UI's file folder
	public $url; // Holds the URL to this UI's file folder
	public $uiName; // Holds the name of this UI block (based on the class name without the UI suffix)
	public $uiPath; // An array containing the name of this block and its ancestors, representing the position of this UI in the UI hierarchy.
	public $uiPathString; // The above uiPath in string format (e.g. layout.store.cart)
	public $depth; // Integer value representing how many blocks deep this block is in the current block hierarchy
	protected $uiBlocks; // holds reference to the UiBlocks module


	function __construct(array $options = null) {
		$this->uiBlocks = $this->wire('modules')->get('UiBlocks');

		// Copy values from the options array to public properties
		if($options) {
			foreach($options as $key => $val) {
				$this->$key = $val;
			}
		}

		$this->path = $this->getPath();
		$this->url = $this->getUrl();
		$this->uiName = $this->getUiName();

		$this->view = new TemplateFile();
		$viewFileName = $this->path . $this->uiName . '.php';
		if(file_exists($viewFileName)) $this->view->setFilename($viewFileName); // Load the default view file for this controller, which is expected to be in the same folder as the controller file

		$this->setup();

		if($this->requestIsAjax() || $this->requestIsAction()) {
			// No need to deal with assets if this is ajax or an action request
		}
		else {
			$this->autoIncludeAssets();

			if ($this->minify && ($this->uiBlocks->proCache || $this->uiBlocks->aiom)) {
				$this->minify();
			}

			$this->passAssets();
		}
	}

	/**
	 * Factory for building UI objects. This is an alternative to using "new" directly. Allows for more compact code.
	 * @param $options - Configuration options to send to the UI's constructor function.
	 */
	public static function build($uiName, array $options = array()) {
		$lastTwo = substr($uiName, -2);
		if($lastTwo !== 'Ui' && $lastTwo !== 'UI') $uiName = $uiName . 'Ui';
		$uiName = '\\ProcessWire\\' . $uiName;
		return new $uiName($options);
	}

	/**
	 * Subclasses may use setup() as a constructor and it will be called when the class is instantiated
	 * This is a good place to set default properties and add js and css files to the headScripts, footScripts, and styles arrays
	 */
	protected function setup() {}

	/**
	 * @param string $className Optional name of class to get the UI name from (uses the current class otherwise)
	 * @return string The name of the current UI, without the namespace or "UI" suffix
	 */
	public function getUiName($className = null) {
		if(!$className && $this->uiName) {
			return $this->uiName;
		}

		if(!$className) $className = $this->className();
		$nameParts = explode('\\', $className);
		$className = array_pop($nameParts);
		$uiName = substr($className, 0, -2); // Remove "UI" from the class name to get just the name of the user interface
		return $uiName;
	}

	/**
	 * Returns the file name of the current class, even if this is a child class. This is a workaround for the limitations of __FILE__
	 */
	public function getClassFile($className = null) {
		$class = $className ?: $this;
		$reflection = new \ReflectionClass($class);
		$file = $reflection->getFileName();
		return $file;
	}

	/**
	 * Returns the path of the current UI directory
	 */
	public function getPath($className = null) {
		if(!$className && $this->path) {
			return $this->path;
		}

        return str_replace('\\', '/', dirname($this->getClassFile($className)))  . '/';
	}

	/**
	 * Returns the URL of the current UI directory
	 */
	public function getUrl($className = null) {
		if(!$className && $this->url) {
			return $this->url;
		}

		$path = $className ? $this->getPath($className) : $this->getPath();
		$pathAfterTemplatesFolder = str_replace($this->config->paths->templates, '', $path);
		return $this->config->urls->templates . $pathAfterTemplatesFolder;
	}

	/**
	 * Passes all public properties of the controller to the view for convenience
	 */
	protected function passPropertiesToView() {
		$excludedProperties = [
			'localHooks',
			'useFuel',
			'view',
			'ajax',
			'trackChanges',
			'changes'
		];

		foreach($this as $name => $value) {
			if(substr($name, 0, 1) !== '_' && !in_array($name, $excludedProperties)) { // Don't include properties that start with "_" or that are in the exclusions array
				$this->view->set($name, $value);
			}
		}
	}

	/**
	 * If there is a JS or CSS file with this UI's namesake, include it (if it isn't already added)
	 */
	private function autoIncludeAssets() {

		// Include any assets from parent UI classes first
		foreach(class_parents($this, false) as $parentClassName) {
			$parentClass = new \ReflectionClass($parentClassName);
			if($parentClass->isSubclassOf("\\ProcessWire\\Ui")) {

				if($this->uiBlocks->useFootScripts) {
					$this->autoIncludeAsset('js', 'footScripts', $parentClassName);
				}
				else {
					$this->autoIncludeAsset('js', 'headScripts', $parentClassName);
				}
				$this->autoIncludeAsset('css', 'styles', $parentClassName);
			}
			else break;
		}

		if($this->uiBlocks->useFootScripts) {
			$this->autoIncludeAsset('js', 'footScripts');
		}
		else {
			$this->autoIncludeAsset('js', 'headScripts');
		}

		$this->autoIncludeAsset('css', 'styles');
	}

	private function autoIncludeAsset($ext, $destinationArray, $className = null) {
		$fileName = $this->getUiName($className) . '.' . $ext;
		$filePath = $this->getPath($className) . $fileName;
		$fileUrl = $this->getUrl($className) . $fileName;

		$exists = false;
		if($ext == 'js') {
			if(file_exists($filePath) && !in_array($fileUrl, $this->headScripts) && !in_array($fileUrl, $this->footScripts))
				$exists = true;
		}
		elseif($ext == 'css') {
			if(file_exists($filePath) && !in_array($fileUrl, $this->styles)) {
				$exists = true;
			}
		}

		if($exists) {
			// If minifying, don't include the version query string for cache busting (the cache module will handle that)
			if($this->version && (!$this->minify || (!$this->uiBlocks->proCache && !$this->uiBlocks->aiom))) {
				$versionSuffix = $this->version ? "?v={$this->version}" : '';
				$fileUrl .= $versionSuffix;
			}
			$this->$destinationArray[] = $fileUrl;
		}
	}

	/**
	 * Handles minification of an arbitrary array of js or css assets through the ProCache or AIOM (All In One Minify) module
	 * @param Array $assets to be minified
	 * @param String $type 'js' | 'css'
	 */
 	protected function minifyAssets($assets, $type) {
		$output = array();
		$count = count($assets);

		if (!$count) return $assets;

		// Remove templates path from beginning of file name, since ProCache and AIOM expects a templates-relative path
		foreach($assets as $key => $val)
			$assets[$key] = str_replace($this->config->urls->templates, '', $val);

		switch($type) {
			case 'js':
				if($this->uiBlocks->proCache) {
					$minifiedPath = $this->uiBlocks->proCache->js($assets);
				}
				elseif($this->uiBlocks->aiom) {
					$minifiedPath = \AIOM::JS($assets);
				}
				break;

			case 'css':
				if($this->uiBlocks->proCache) {
					$minifiedPath = $this->uiBlocks->proCache->css($assets);
				}
				elseif($this->uiBlocks->aiom) {
					$minifiedPath = \AIOM::CSS($assets);
				}
				break;

			default:
				$minifiedPath = '';
		}

		$output[] = $minifiedPath;

		return $output;

	}

	/**
	 * @return Array of assets with absolute paths (cannot be minified)
	 */
	protected function getExternalAssets($array, $location) {
		$output = array();

		foreach ($array as $key => $val) {
			if (!in_array($location, array('headScripts', 'footScripts', 'styles')))
				return;

			if (strpos($val, 'http://') !== false || strpos($val, 'https://') !== false) {
				if (gettype($val) === 'string')
					$output[] = $val;
			}
		}

		return $output;
	}

	/**
	 * Passes all local scripts and styles to the global assets arrays for inclusion in the html
	 */
	protected function passAssets() {
		$this->uiBlocks->headScripts = array_unique(array_merge($this->uiBlocks->headScripts, $this->headScripts));
		$this->uiBlocks->footScripts = array_unique(array_merge($this->uiBlocks->footScripts, $this->footScripts));
		$this->uiBlocks->styles      = array_unique(array_merge($this->uiBlocks->styles,      $this->styles));
	}

	protected function minify() {
		// Separate out the external assets so we can include them separately (they cannot be minified)
		$headScriptsExternal = $this->getExternalAssets( $this->headScripts, 'headScripts' );
		$footScriptsExternal = $this->getExternalAssets( $this->footScripts, 'footScripts' );
		$stylesExternal      = $this->getExternalAssets( $this->styles,      'styles'      );

		$headScriptsToMin = array_diff($this->headScripts, $headScriptsExternal);
		$footScriptsToMin = array_diff($this->footScripts, $footScriptsExternal);
		$stylesToMin = array_diff($this->styles, $stylesExternal);

		$headScriptsMin = $this->minifyAssets( $headScriptsToMin, 'js'  );
		$footScriptsMin = $this->minifyAssets( $footScriptsToMin, 'js'  );
		$stylesMin      = $this->minifyAssets( $stylesToMin,      'css' );

		// Update the asset arrays
		$this->headScripts = array_merge($headScriptsExternal, $headScriptsMin);
		$this->footScripts = array_merge($footScriptsExternal, $footScriptsMin);
		$this->styles = array_merge($stylesExternal, $stylesMin);
	}

	/**
	 * Child classes put their code in here. This is the main method, called when the UI block is rendered.
	 */
	protected function run() {}


	/**
	 * Called after the view is rendered. Child classes can use this to execute any controller logic post-view.
	 */
	protected function end() {}

	/**
	 * @return string to include before the contents of the view (feel free to override in subclasses)
	 */
	protected function header() {
		$name = $this->getUiName();
		$id = $this->sanitizer->entities1($this->id);
		$uiId = empty($id) ? '' : "ui_$id";
		$htmlId = empty($id) ? '' : "id='ui_$id'";
		$path = $this->sanitizer->entities1($this->uiPathString);

		$url = '';
		if($this->uiBlocks->ajaxRequestUrl !== null && $this->isTargetBlock()) {
			$ajaxRequestUrl = $this->sanitizer->entities1($this->uiBlocks->ajaxRequestUrl);
			$url = "data-ui-url='$ajaxRequestUrl'";
		}

		$attributes = '';
		foreach($this->wrapperAttributes as $attrKey => $attrValue) {
			$attributes .= $this->sanitizer->entities1($attrKey) . '="' . $this->sanitizer->entities1($attrValue) . '" ';
		}

		return "<div $htmlId class='ui ui_$name $uiId {$this->sanitizer->entities1($this->classes)}' data-ui-name='$name' data-ui-id='$id' data-ui-path='$path' $url $attributes>";
	}

	/**
	 * @return string to include after the contents of the view (feel free to override in subclasses)
	 */
	protected function footer() {
		return "</div>";
	}

	/**
	 * Runs the controller, passes variables to the view and calls render() on it
	 * @return string output from view TemplateFile
	 */
	protected function renderView() {
		$result = $this->run(); // Run the controller

		// If the run method returned an associative array of variables, we pass them to the view
		if(is_array($result)) {
			foreach($result as $key => $value) {
				$this->view->set($key, $value);
			}
		}

		$this->view->ui = $this; // Pass the UI object to the view
		$this->passPropertiesToView();

		$output = '';
		if($this->wrapper) $output .= $this->header();
		$output .= $this->view->render();
		if($this->wrapper) $output .= $this->footer();
		return $output;
	}

	/**
	 * Called by the client to run the user interface controller logic and output its markup (or ajax array, if this is an ajax call)
	 */
	public function render() {
		if($this->debug === null) $this->debug = $this->config->debug; // If debug mode is not set, use PW's global debug setting

		$this->uiPath = $this->uiBlocks->currentUiPath;
		$this->uiPath[] = $this->id ?: $this->getUiName();
		$this->depth = count($this->uiPath);
		$this->uiBlocks->currentUiPath = $this->uiPath;
		$this->uiPathString = implode('.', $this->uiPath);

		if($this->uiBlocks->checkPath && (($this->requestIsAjax() || $this->requestIsAction()) && !$this->isInUiPath())) {
			//echo "skipped {$this->id} at depth {$this->depth} | ";
			array_pop($this->uiBlocks->currentUiPath);
			return; // Do not run this block if this is an ajax request and the block is not in the ajax path
		}
		else {
			if(($this->requestIsAjax() || $this->requestIsAction()) && $this->isTargetBlock()) {
				if($this->uiBlocks->checkPath && !$this->isInUiPath()) {
					throw new WireException('Invalid UI path.');
				}

				$this->uiBlocks->checkPath = false; // Since we hit the target block & method, blocks we render from here should not have their paths checked

				if($this->requestIsAjax()) {
					$method = 'ajax_' . $this->input->ajax;
				}
				else {
					$method = 'action_' . $this->input->action;
				}

				if(!method_exists($this, $method)) {
					throw new WireException("The method $method does not exist on the requested UI Block.");
				}
				else {
					// Pass the relevant input variables to the function we're calling
					$input = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET;
					// Don't include these keys in the input array
					unset($input['ui']);
					unset($input['ajax']);
					unset($input['action']);

					// Call the function
					$return = $this->$method($input);

					if ($this->requestIsAjax()) {
						// If a return value is given, it will replace or be merged with the ajax array
						if ($return !== null) {
							if (is_array($return)) {
								$this->ajax = array_merge($this->ajax, $return);
							}
							else {
								$this->ajax = $return;
							}
						}

						$this->outputAjax();
					}
					else { // This is an action request
						// If a string is returned, we assume it's the url to redirect to
						if (is_string($return)) {
							$this->session->redirect($return);
						}
						// If an array is returned, we assume it's a set of get parameters to add to the current url, such as "success=1"
						elseif (is_array($return)) {
							$queryString = '?' . http_build_query($return);
							$this->session->redirect($this->input->url() . $queryString);
						}
						// Otherwise, just refresh the current page. This is to help enforce the post-reload-get pattern.
						else {
							$this->session->redirect($this->input->url());
						}
					}
				}
			}
			else { // Render the view
				//echo "rendered {$this->id} at depth {$this->depth} | ";
				$output = $this->renderView();
				$this->end();

				array_pop($this->uiBlocks->currentUiPath);

				return $output;
			}
		}
	}

	/**
	 * Alias for render()
	 */
	public function output() {
		return $this->render();
	}

	/**
	 * Directly outputs the UI's ajax data array in JSON format and halts further program execution (Used for AJAX calls)
	 */
	protected function outputAjax() {
		// Before outputting our JSON, we remove any markup that may have already been sent to php output buffers (e.g. if the UI Block was embedded inside of a view instead of pre-rendered in the controller, or the layout is not itself a UI Block)
		while (ob_get_level()) {
			ob_end_clean();
		}

		header('Content-Type: application/json');
		echo json_encode($this->ajax);
		exit;
	}

	/**
	 * Built-in ajax function to return the view of any UI
	 * Produces JSON array with single "view" element containing the html markup
	 */
	protected function ajax_reload() {
		$this->ajax['view'] = $this->renderView();
	}

	/**
	 * Was this UI loaded as part of an ajax request?
	 * If debug mode is off and the url is accessed directly, this will return false so that the result of an ajax request will not be shown in browser
	 *
	 * @return bool
	 */
	protected function requestIsAjax() {
		return $this->input->ajax && $this->input->ui && ($this->config->ajax || $this->debug === true);
	}

	/**
	 * Was this UI loaded as part of an action request?
	 * Actions are non-ajax post requests that include a specific UI path and function name.
	 * They provide the efficiency of an ajax request but end in a redirect rather than returning markup or JSON
	 *
	 * @return bool
	 */
	protected function requestIsAction() {
		return $_SERVER['REQUEST_METHOD'] == 'POST' && $this->input->post->ui && $this->input->post->action;
	}

	/**
	 * For ajax and standard post requests, checks whether this block is in the UI path that was requested (?ajax=methodToExecute&ui=block1.block2.block3...)
	 * This allows us to avoid executing blocks that are not relevant to the ajax or post request
	 * @return bool
	 */
	protected function isInUiPath() {
		$uiRequestedAtCurrentDepth = $this->uiBlocks->uiRequestedAtCurrentDepth();
		if(!$uiRequestedAtCurrentDepth) return false;

		if($this->id === $uiRequestedAtCurrentDepth || (!$this->id && $this->getUiName() === $uiRequestedAtCurrentDepth)) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * For ajax requests, checks if this block is the target of the request (the final block in the path with the method we are executing)
	 * @return bool
	 */
	protected function isTargetBlock() {
		return count(explode('.', $this->input->ui)) === $this->depth;
	}

	/**
	 * @return mixed|null If a UI object is echoed directly to the page, it will automatically render itself
	 */
	public function __toString() {
		return $this->render();
	}

}