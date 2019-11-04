<?php namespace ProcessWire;
class LayoutUI extends UI {

	// Public properties ('props') that can be configured for each instance of the UI Block. Props are automatically passed to the view.
	public $version = 1;
	public $id = 'layout'; // A unique identifier for this block (used for ajax calls)
	public $browserTitle = '';
	public $wrapper = false; // Don't wrap the root layout UI block in a div

	// Content Regions
	public $main = '';

	/**
	 * Set up any additional styles and/or scripts (other than Layout.css and Layout.js) that are required by the layout.
	 */
	protected function setup() {
		//$this->styles[] = "{$this->config->urls->templates}library/myStyles.css";
		$this->headScripts[] = "https://code.jquery.com/jquery-3.1.1.min.js"; // Include your version of jQuery
		$this->headScripts[] = "{$this->config->urls->siteModules}UIBlocks-master/UI.js"; // Include the main UI.js file to use the build in ajax functions
	}

	/**
	 * Called when the block is rendered. Your code goes here.
	 */
	protected function run() {
		// Example of passing a variable from the controller to the view
		$this->browserTitle = $this->page->title;
	}

}