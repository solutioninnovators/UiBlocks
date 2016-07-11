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
	public $version = 1; // Update the version number of a user interface to force browsers to reload cached js & css assets
	public $path = ''; // Path to the UI directory
	public $url = ''; // URL to the UI directory
	public $headScripts = array();
	public $footScripts = array();
	public $styles = array();

	function __construct() {
		$this->path = $this->getPath();
		$this->url = $this->getUrl();

		$this->setup();
		if(!$this->view) {
			$this->view = new TemplateFile(dirname($this->getClassFile())."/{$this->getUiName()}.php"); // Load the default view file for this controller, which is expected to be in the same folder as the controller file
		}
	}

	/**
	 * Subclasses may use setup() as a constructor and it will be called when the class is instantiated
	 * This is a good place to set default properties and add js and css files to the headScripts, footScripts, and styles arrays
	 */
	protected function setup() {}

	public function getUiName() {
		$nameParts = explode('\\', get_class($this));
		$className = array_pop($nameParts);
		$uiName = substr($className, 0, -2); // Remove "UI" from the class name to get just the name of the user interface
		return $uiName;
	}

	/**
	 * Returns the file name of the current class, even if this is a child class. This is a workaround for the limitations of __FILE__
	 */
	public function getClassFile() {
		$reflection = new \ReflectionClass($this);
		$file = $reflection->getFileName();
		return $file;
	}

	/**
	 * Returns the path of the current UI directory
	 */
	public function getPath() {
		return dirname($this->getClassFile()) . '/';
	}

	/**
	 * Returns the URL of the current UI directory
	 */
	public function getUrl() {
		return $this->config->urls->templates . "ui/" . $this->getUiName() . "/";
	}

	/**
	 * Passes all ProcessWire API variables to the view for convenience
	 */
	protected function passWireVarsToView() {
		$wireVars = $this->wire();
        $this->view->set('wire', $wireVars);
        foreach($wireVars as $key => $value)
			$this->view->set($key, $value);
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
	protected function autoIncludeAssets() {
		$versionSuffix = $this->version ? "?v={$this->version}" : '';

		$fileName = "{$this->getUiName()}.js";
		$filePath = $this->path . $fileName;
		$fileUrl = $this->url . $fileName;

		if(file_exists($filePath) && !in_array($fileUrl, $this->headScripts) && !in_array($fileUrl, $this->footScripts)) {
			$this->headScripts[] = $fileUrl . $versionSuffix;
		}

		$fileName = "{$this->getUiName()}.css";
		$filePath = $this->path . $fileName;
		$fileUrl = $this->url . $fileName;

		if(file_exists($filePath) && !in_array($fileUrl, $this->styles)) {
			$this->styles[] = $fileUrl . $versionSuffix;
		}
	}

	/**
	 * Passes all local scripts and styles to the layout (outermost UI) for inclusion in the html
	 * @todo: Should this be modified to use $config, or make the layout object configurable?
	 */
	protected function passAssetsToLayout() {
		$this->layout->headScripts = array_unique(array_merge($this->layout->headScripts, $this->headScripts));
		$this->layout->footScripts = array_unique(array_merge($this->layout->footScripts, $this->footScripts));
		$this->layout->styles = array_unique(array_merge($this->layout->styles, $this->styles));
	}

	/**
	 * Child classes put their code in here. Called when the interface is output().
	 */
	protected function run() {}

	/**
	 * Called by the client to run the user interface and output its markup
	 */
	public function output() {
		$this->autoIncludeAssets();
		$this->run();
		$this->passWireVarsToView();
		$this->passPublicPropertiesToView();
		$this->passAssetsToLayout();

		return $this->view->render();
	}
	
}