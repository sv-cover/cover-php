<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing the Editable data
	  */
	class DataModelEditable extends DataModel {
		function DataModelEditable($db) {
			parent::DataModel($db, 'pages');
		}
		
		/**
		  * Gets an editable page from a title
		  * @title the title of the editable page
		  * 
		  * @result a #DataIter or null of no such page could be
		  * found
		  */
		function get_iter_from_title($title) {
			return $this->_row_to_iter($this->db->query_first("SELECT * 
					FROM pages
					WHERE titel = '" . $this->escape_string($title) . "'"));
		}

		function get_content($id, &$page = null)
		{
			$page = $this->get_iter($id);

			$lang_spec_prop = 'content_' . i18n_get_language();

			return !empty($page->data[$lang_spec_prop])
				? $page->get($lang_spec_prop)
				: $page->get('content');
		}

		function get_title($id)
		{
			$content = $this->get_content($id, $page);

			return preg_match('/^\[h1\](.*?)\[\/h1\]/i', $content, $match)
				? $match[1]
				: null;
		}

		function get_summary($id)
		{
			$content = $this->get_content($id, $page);

			return editable_get_summary($content, $page->get('owner'));
		}
	}
