# UI Blocks - A component-based framework for building modular, nestable graphical user interfaces in ProcessWire using MVC principals. Similar to Vue or React components, but server-side.
Developed by Mike Spooner (thetuningspoon) for Solution Innovators

## Usage
1. Install the module in the ProcessWire admin
2. Create a /site/templates/ui directory. This is where you will put your UI Blocks, which represent the individual components of your user user interface. Each UI Block has a controller and a view file.
3. Create a /site/templates/models directory. This is where you will put your data model classes, most of which will extend ProcessWire's Page class. 
4. For each UI block you want to create, add a directory under /site/templates/ui/ with the format "MyBlockName"
5. Inside /site/templates/ui/MyBlockName/ create files for MyBlockName.php (this is your view file) and MyBlockNameUi.php (your controller file). You may also create MyBlockName.css and MyBlockName.js for any css and javascript that applies to the block. Other assets tied to the block (e.g. images) can also be stored in the block's directory. UI Blocks also supports nesting of directories for related blocks.
6. Edit your MyBlockNameUi.php and add the class definition for MyBlockNameUi, extending the Ui class. Make sure you add the ProcessWire namespace at the top of the file.
7. Typically, you will create a single router.php or main.php template file under /site/templates/ that ProcessWire can use to direct all requests through. In your template settings, set the "Alternate template file name" to use your router template file.  
8. In your template file, you can call a block using (new MyBlockNameUi($props))->render()
9. Pass a variable from your controller to your view using $this->view->foo = $foo;

See examples in /move-to-your-templates-folder/ 

## Ajax
With the ui.js file loaded on your site, any UI Block on the page can be refreshed at any time through javascript, without having to reload the entire page. Just give your block a unique $id property and call UiBlocks.reload($('.ui_myUiId'))

UI Blocks also provides a clean methodology for organizing your ajax calls and their endpoints. Instead of creating separate files to handle your ajax calls, the endpoint methods that apply to each UI block are included in each UI block's controller. 

1. Add a method to your controller prepended with "ajax_". For example, "ajax_myMethodName". 
2. Have your method populate the $this->ajax array with whatever json data you want to return to javascript. 
3. To call your method in your javascript code, simply call the current page with ajax=myMethodName and ui=parentBlock.myBlockId in the query string (NOTE: The ui value to use here can be retrieved from the block's parent element's ui-path attribute, which is automatically generated in the document's HTML)
