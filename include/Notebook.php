<?php
	if (!defined('IN_SITE'))
		return;
	
	/**
	  * Class for creating a tabbed notebook
	  */
	class Notebook {
		var $pages = null;
		var $identifier = null;
		var $selected = 0;
		var $classname;

		/**
		  * Notebook constructor
		  * @identifier the identifier of the notebook (may only 
		  * contain letters and underscores). This is used in the
		  * generated html to identify this notebook and it should
		  * therefore be unique for every notebook on one page
		  * @classname the class of the notebook (to be used to 
		  * define a different style on the notebook)
		  */
		function Notebook($identifier, $classname = 'notebook') {
			$this->identifier = $identifier;
			$this->pages = array();
			$this->classname = $classname;
		}
		
		/**
		  * Add a new page to the notebook
		  * @caption the caption of the tab
		  * @contents the contents of the notebook page
		  * @selected optional; whether this page should be selected 
		  * initially. By default the first tab is selected
		  */
		function add_page($caption, $contents, $selected = false) {
			$page = array('caption' => $caption, 'contents' => $contents);
			
			$this->pages[] = $page;
			
			if ($selected)
				$this->selected = count($this->pages) - 1;
		}
		
		function _render_page($i) {
			return '<div id="' . $this->identifier . '_page_' . $i . '" class="' . $this->classname . '_page">' .
					$this->pages[$i]['contents'] . "</div>\n";
		}
		
		/**
		  * Render the notebook (return the html for the notebook)
		  *
		  * @result the generated html for the notebook
		  */
		function render() {
			static $rendered = false;

			$result = '';

			if (!$rendered) {
				$rendered = true;
				$result = '<script type="text/javascript" src="data/notebook.js"></script>';
			}

			$result .= '<script type="text/javascript">var notebook_' . $this->identifier . ' = new Notebook("' . $this->identifier . '", ' . $this->selected . ');</script>
				<div class="' . $this->classname . '">
					<ul class="' . $this->classname . '">';
			
			$pages = '';

			for ($i = 0; $i < count($this->pages); $i++) {
				$page = $this->pages[$i];
				$result .= '<a href="javascript:show_notebook_page(notebook_' . $this->identifier . ', ' . $i . ')"><li id="' . $this->identifier . '_tab_' . $i . '">' . $page['caption'] . "</li></a>";
				
				$pages .= $this->_render_page($i);
			}
			
			$result .= '</ul><div class="' . $this->classname . '_contents">' . $pages . '</div></div>';
			$result .= '<script type="text/javascript">show_notebook_page(notebook_' . $this->identifier . ', ' . $this->selected . ');</script>';
			
			return $result;
		}
	}
?>
