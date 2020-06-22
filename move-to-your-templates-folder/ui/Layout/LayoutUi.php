<?php namespace ProcessWire;
class LayoutUi extends Ui {

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
		$this->headScripts[] = "{$this->config->urls->siteModules}UIBlocks-master/Ui.js"; // Include the main Ui.js file to use the build in ajax functions
	}

	/**
	 * Called when the block is rendered. Your code goes here.
	 */
	protected function run() {
		// Example of passing a variable from the controller to the view
		$this->browserTitle = $this->page->title;
	}

}