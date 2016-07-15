<?php namespace ProcessWire;
/**
 * All templates that make use of MarkupHMVC should point to this file in the ProcessWire admin
 */

$layout = new LayoutUI(); // Instantiate the parent user interface, which is the layout
$this->wire('layout', $layout); // Add the layout object to the api, for setting shared variables like scripts and styles that should be accessible to all child interfaces

echo $layout->output();