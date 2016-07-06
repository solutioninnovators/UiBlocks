<?php namespace ProcessWire;
class LayoutUI extends UI {

  public $styles = array();
  public $headScripts = array();
  public $footScripts = array();
  public $browserTitle = '';
	public $pageTitle = '';
	public $version = 1;
	public $modal = false;

	protected function setup() {
		$this->styles[] = "https://www.solutioninnovators.com/shared/styles/si-layout-1.2.php?gridSize=12&gutterWidth=4&breakpoints=1000px|850px|800px|650px|600px|450px";
		//$this->styles[] = "https://fonts.googleapis.com/css?family=Source+Sans+Pro:600,600italic,200,200italic,300,300italic,700,700italic|Antic|Antic+Slab";
		//$this->styles[] = "{$this->config->urls->templates}library/linear-icons/style.css";
		//$this->styles[] = "{$this->config->urls->templates}library/font-awesome-4.3.0/css/font-awesome.min.css";
		//$this->styles[] = "{$this->config->urls->modules}Jquery/JqueryMagnific/JqueryMagnific.css";
		//$this->styles[] = "{$this->config->urls->templates}library/si-modal/si-modal.css";

		$this->headScripts[] = "{$this->config->urls->templates}library/jquery-1.8.3.min.js";
		$this->headScripts[] = "{$this->config->urls->templates}library/modernizr.custom.62006.js";
		$this->headScripts[] = "{$this->config->urls->templates}library/picturefill.min.js";
		$this->headScripts[] = "{$this->config->urls->templates}library/jquery.html5-placeholder-shim.js";
		//$this->headScripts[] = "{$this->config->urls->templates}library/jquery.matchHeight-min.js";
		//$this->headScripts[] = "{$this->config->urls->modules}Jquery/JqueryMagnific/JqueryMagnific.js";
		//$this->headScripts[] = "{$this->config->urls->templates}library/jquery.columnizer.js";
		//$this->headScripts[] = "{$this->config->urls->templates}library/cycle2/build/jquery.cycle2.min.js";
		//$this->headScripts[] = "{$this->config->urls->templates}library/si-modal/si-modal.js";

		if($this->input->get->modal) $this->modal = true; // Use modal mode if modal param present in URL
	}

    protected function run() {
        // Decide on which UI to use for the body of the page based on the name of the current page's template
        $className = 'ProcessWire\\' . $this->page->template . 'UI';
        $template = new $className();
		// If the layout is bypassed or this is a modal window, we load the template's view directly instead of the layout's view
		if($template->bypassLayout == true) {
			$this->view = $template->view;
		}
		$this->view->set('template', $template->output());

		$this->pageTitle = $this->page->title;

		$globalTitle = $this->page->path != '/' ? $this->pages->get(1011)->global_title : '';
        $this->browserTitle = $this->page->get('browser_title|title') . ' ' . $globalTitle;

		$breadcrumbs = $this->page->parents;
		$this->view->set('breadcrumbs', $breadcrumbs);
    }

}