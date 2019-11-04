<?php namespace ProcessWire;

/**
 * Example of extending the page class to add functionality for a particular template. You must turn on advanced mode in config.php and set the template's Page class name to BasicPage
 */
class BasicPage extends Page {
	public function __construct(Template $tpl = null)
	{
		parent::__construct($tpl);
		if (is_null($tpl)) {
			$this->template = wire('templates')->get('basic-page');
		}
		//$this->parent = wire('pages')->get('template=basic-pages'); // Set a parent page to use as the default, if applicable
	}

	public function doSomethingCool() {
		return $this->title . ' is doing something cool!';
	}
}