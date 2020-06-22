<?php namespace ProcessWire;
class WidgetUi extends Ui {

	public $version = 1; // Updating the version number will bust the browser cache for the js and css files associated with this block
	public $id = 'bar'; // Example of setting a default prop value
	public $message; // Declaring prop with null value

	protected function setup() {}

	protected function run() {
		// If prop wasn't set in the calling code, set it now
		if(!$this->message) {
			$this->message = 'This is the default message.';
		}
	}
}