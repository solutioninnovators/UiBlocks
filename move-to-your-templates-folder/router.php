<?php namespace ProcessWire;
/**
 * Determine the UI Blocks to load for this request and output them
 * Set your template's "Alternate template file name" to use this file
 */

$layout = new LayoutUI(); // Load the root UI block (html header & footer)

$className =  '\\ProcessWire\\' . $sanitizer->pascalCase($this->page->template) . 'UI'; // Look for a PascalCase UI Block that matches the template name of the page we're currently on (you could also base this on a page field, or use a case block)

$layout->main = (new $className(['wrapper' => false]))->render(); // Render the block we found and place its rendered html into the layout's $main content area

echo $layout->render(); // Render the root layout block and echo the final page back to the user