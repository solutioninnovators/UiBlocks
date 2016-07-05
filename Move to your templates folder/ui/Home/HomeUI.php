<?php namespace ProcessWire;
class HomeUI extends UI {

	/**
	 * Declare properties (if needed)
	 * Public properties are automatically sent to the view.
	 * Public properties can be accessed by the parent UI, so this is a good way to make the UI configurable.
	 */
	protected $version = 1; // Update the version number of a user interface to force browsers to reload cached js & css assets

	/**
	 * This is called when the object is first instantiated by the parent UI (client code). It is a good place to set default properties for this UI and add js and css files to the $this->headScripts, $this->footScripts, and $this->styles arrays. If you create a JS or CSS file with the same name as the template, it will be automatically included. So this function can be removed if not needed.
	 */
	protected function setup() {}

	/**
	 * This is called when the output() function is called by the parent UI (client code).
	 * Put any form processing logic in here. You can also instantiate other UI sub-blocks and assign variables to the view file using $this->view->set('variableName', $variableContent);
	 * If your UI is complex enough, you can (and should) break your code into separate functions and call them from here.
	 */
	protected function run() {
		/*
		// Example of calling a UI sub-block and sending its output to the view (HMVC):

		$subUI = new subUI();
		$subUI->someProperty = someValue; // Set a property

		$this->view->set('subUI', $subUI->output());
		*/

		// Example of sending a simple variable to the view:
		$foo = "I am foo.";
		$this->view->set('foo', $foo);
	}
}