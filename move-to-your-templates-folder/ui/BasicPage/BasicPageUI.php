<?php namespace ProcessWire;
class BasicPageUI extends UI {

	protected function setup() {}

	protected function run() {
		// Example of rendering the same UI block multiple times, passing in different props for each
		$this->view->widget1 = (new WidgetUI())->render();
		$this->view->widget2 = (new WidgetUI(['id' => 'Foo', 'message' => 'This is a customized message.']))->render();
	}
}