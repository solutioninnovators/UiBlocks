<?php namespace ProcessWire;
class BasicPageUi extends Ui {

	protected function setup() {}

	protected function run() {
		// Example of rendering the same UI block multiple times, passing in different props for each
		$this->view->widget1 = (new WidgetUi())->render();
		$this->view->widget2 = (new WidgetUi(['id' => 'Foo', 'message' => 'This is a customized message.']))->render();
	}
}