<?php namespace ProcessWire;
/**
 * Class UI is the base class for all user interfaces. This file is the equivalent of the "controller" in traditional MVC (Model-View-Controller) lingo.
 *
 * A "user interface" in our case represents any collection of php logic, markup/html, css, and javascript used in generating the graphical user interface (GUI) or portion of the GUI that the end user interacts with.
 *
 * Classes extending the UserInterface class should contain all logic for handling user input (http requests) and preparing data for output.
 *
 * User interfaces make use of a separate "view" file for html markup (TemplateName.php by default), which uses ProcessWire's native TemplateFile class. Only presentational logic should be contained in the view file. (e.g. if/thens for showing/hiding content and simple loops for lists and tables)
 *
 * Business logic should be handled by the "model", which in this case is represented by ProcessWire Pages. To add custom properties and methods to a page, the Page class should be extended using a custom module.
 *
 * All css, js, and html for an interface are grouped together in a directory with the same name in the templates folder. (i.e. "feature folders")
 *
 * Interfaces are nestable (also known as HMVC), meaning any interface can call/instantiate another interface and assign its output to a variable which can be placed within its (the parent interface's) view. This allows for a modular architecture where each user interface "widget" can have its own controller, view, js, and css files.
 *
 *
 * Framework by Michael J Spooner for Solution Innovators (2015)
 *
 */
abstract class UI extends Wire {

	protected $view; // The view file (HTML markup/template) this user interface will use for output
	protected $ajax = array(); // An ajax data array to output instead of the view for ajax requests
	public $id; // A unique identifier for the particular instance of the UI (useful mainly for AJAX calls)
 	public $version; // Update the version number of a user interface to force browsers to reload cached js & css assets
	public $path = ''; // Path to the UI directory
	public $url = ''; // URL to the UI directory
	public $uiName = '';
	public $headScripts = array();
	public $footScripts = array();
	public $styles = array();
	public $minify = true;
	public $wrapper = true; // Enable/disable the header and footer markup surrounding the block
	public $classes = ''; // Classes to add to the wrapper
	public $debug = null; // Allows AJAX data to be output even if $config->ajax is false. May be used to switch on other debug data as needed. If not set, the value from PW's global $config->debug will be used
	public $allowAjaxConstruct = false; // Allows the UI to be constructed by an ajax call on any page before any other controllers are executed. See line 60 of UiBlocks-master for more information

	function __construct(array $options = null) {
		// Copy values from the options array to public properties
		if($options) {
			foreach($options as $key => $val) {
				if(property_exists($this, $key)) {
					$this->$key = $val;
				}
			}
		}

		$this->path = $this->getPath();
		$this->url = $this->getUrl();
		$this->uiName = $this->getUiName();

		$this->view = new TemplateFile();
		$viewFileName = $this->path . $this->uiName . '.php';
		if(file_exists($viewFileName)) $this->view->setFilename($viewFileName); // Load the default view file for this controller, which is expected to be in the same folder as the controller file

		$this->setup();

		$this->autoIncludeAssets();
		if($this->minify && $this->modules->isInstalled("AllInOneMinify")) $this->minify();
		$this->passAssets();
	}


	/*public function addHeadScript($file) {
		$this->headScripts[] = $file;
		$this->modules->UiBlocks->headScripts[] = $file;
	}

	public function addFootScript($file) {
		$this->footScripts[] = $file;
		$this->modules->UiBlocks->footScripts[] = $file;
	}

	public function addStyle($file) {
		$this->styles[] = $file;
		$this->modules->UiBlocks->styles[] = $file;
	}*/

	/**
	 * Factory for building UI objects. This is an alternative to using "new" directly. Allows for more compact code.
	 * @param $options - Configuration options to send to the UI's constructor function.
	 */
	public static function build($UIname, array $options = array()) {
		if(substr($UIname, -2) != 'UI') $UIname = $UIname . 'UI';
		$UIname = '\\ProcessWire\\' . $UIname;
		return new $UIname($options);
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
	protected function passPublicPropertiesToView() {
		$reflection = new \ReflectionObject($this);
		$publicProperties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
		foreach($publicProperties as $property){
			$this->view->set($property->getName(), $property->getValue($this));
		}
	}

	/**
	 * If there is a JS or CSS file with this UI's namesake, include it (if it isn't already added)
	 */
	private function autoIncludeAssets() {

		// Include any assets from parent UI classes first
		foreach(class_parents($this, false) as $parentClassName) {
			$parentClass = new \ReflectionClass($parentClassName);
			if($parentClass->isSubclassOf("\\ProcessWire\\UI")) {
				$this->autoIncludeAsset('js', 'headScripts', $parentClassName);
				$this->autoIncludeAsset('css', 'styles', $parentClassName);
			}
			else break;
		}

		$this->autoIncludeAsset('js', 'headScripts');
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
			if(!$this->minify || !$this->modules->isInstalled("AllInOneMinify") && $this->version) {
				$versionSuffix = $this->version ? "?v={$this->version}" : '';
				$fileUrl .= $versionSuffix; // The AIOM module can't handle query strings
			}
			$this->$destinationArray[] = $fileUrl;
		}
	}

	/**
	 * Handles minification of an arbitrary array of js or css assets through the AIOM (All In One Minify) module
	 * @param Array $assets to be minified
	 * @param String $type 'js' | 'css'
	 */
 	protected function minifyAssets($assets, $type) {

		$output = array();
		$count = count($assets);

		if (!$count) return $assets;

		// Remove templates path from beginning of file name, since AIOM expects a templates-relative path
		foreach($assets as $key => $val)
			$assets[$key] = str_replace($this->config->urls->templates, '', $val);

		switch($type) {
			case 'js':
				$minifiedPath = \AIOM::JS($assets);
				break;

			case 'css':
				$minifiedPath = \AIOM::CSS($assets);
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

		foreach ( $array as $key => $val ) {

			if ( ! in_array( $location, array('headScripts', 'footScripts', 'styles') ) )
				return;


			if (
				strpos( $val, 'http://'  ) !== false ||
				strpos( $val, 'https://' ) !== false
			) {
				if ( gettype( $val ) === 'string' )
					$output[] = $val;
			}
		}

		return $output;

	}

	/**
	 * Passes all local scripts and styles to the global assets arrays for inclusion in the html
	 */
	protected function passAssets() {
		$this->modules->UiBlocks->headScripts = array_unique(array_merge($this->modules->UiBlocks->headScripts, $this->headScripts));
		$this->modules->UiBlocks->footScripts = array_unique(array_merge($this->modules->UiBlocks->footScripts, $this->footScripts));
		$this->modules->UiBlocks->styles      = array_unique(array_merge($this->modules->UiBlocks->styles,      $this->styles));
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
	 * Child classes put their code in here. Called when the interface is output().
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
		$id = $this->sanitizer->entities($this->id);
		$uiId = empty($id) ? '' : "ui_$id";
		$htmlId = empty($id) ? '' : "id='ui_$id'";

		return "<div $htmlId class='ui ui_$name $uiId {$this->sanitizer->entities($this->classes)}' data-ui-name='$name' data-ui-id='$id'>";
	}

	/**
	 * @return string to include after the contents of the view (feel free to override in subclasses)
	 */
	protected function footer() {
		return "</div>";
	}

	/**
	 * Passes variables to the view and calls render() on it
	 * @return string output from view TemplateFile
	 */
	protected function renderView() {
		$this->view->ui = $this; // Pass the UI object to the view
		$this->passPublicPropertiesToView();

		$output = '';
		if($this->wrapper) $output .= $this->header();
		$output .= $this->view->render();
		if($this->wrapper) $output .= $this->footer();
		return $output;
	}

	/**
	 * Called by the client to run the user interface controller logic and output its markup (or ajax array, if this is an ajax call)
	 */
	public function output() {
		if($this->debug === null) $this->debug = $this->config->debug; // If debug mode is not set, use PW's global debug setting

		if($this->input->ajax && ($this->config->ajax || $this->debug === true) && ($this->input->ui == $this->id || $this->input->ui == $this->getUIName()) && method_exists($this, 'ajax_' . $this->input->ajax)) {
			$method = 'ajax_' . $this->input->ajax;
			$this->$method();
			$this->outputAjaxData();
		}
		else { // Render the view
			$this->run();
			$output = $this->renderView();
			$this->end();

			return $output;
		}
	}

	/**
	 * Alias for output() @todo: rename output() to run(), renderView() to output(), point this alias to output(), and rename run() to main()?
	 */
	public function render() {
		return $this->output();
	}

	/**
	 * Directly outputs the UI's data array in json format and halts further program execution (Used for AJAX calls)
	 * @todo: rename to outputAjax()
	 */
	protected function outputAjaxData() {
		header('Content-Type: application/json');
		echo json_encode($this->ajax); exit;
	}

	/**
	 * Built-in ajax function to return the view of any UI
	 * Produces JSON array with single "view" element containing the html markup
	 */
	protected function ajax_reload() {
		$this->run();
		$this->ajax['view'] = $this->renderView();
	}

}