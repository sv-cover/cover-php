<?php
	require_once 'include/data/DataModel.php';
	require_once 'include/search.php';
	require_once 'include/editable.php';

	class DataIterEditable extends DataIter implements SearchResult
	{
		public function get_content($language = null)
		{
			if (!$language)
				$language = i18n_get_language();

			$preferred_fields = array(
				'content_' . $language,
				'content_en',
				'content');

			foreach ($preferred_fields as $field)
				if ($this->get($field))
					return $this->get($field);

			return null;
		}

		public function get_title($language = null)
		{
			$content = $this->get_content($language);

			return preg_match('/\[h1\](.+?)\[\/h1\]\s*/ism', $content, $match)
				? $match[1]
				: $this->get('titel');
		}

		public function get_search_type()
		{
			return 'page';
		}
	}

	/**
	  * A class implementing the Editable data
	  */
	class DataModelEditable extends DataModel implements SearchProvider
	{
		public $dataiter = 'DataIterEditable';

		public function __construct($db)
		{
			parent::__construct($db, 'pages');
		}
		
		/**
		  * Gets an editable page from a title
		  * @title the title of the editable page
		  * 
		  * @result a #DataIter or null of no such page could be
		  * found
		  */
		public function get_iter_from_title($title)
		{
			return $this->_row_to_iter($this->db->query_first("SELECT * 
					FROM pages
					WHERE titel = '" . $this->db->escape_string($title) . "'"));
		}

		public function get_content($id, &$page = null)
		{
			$page = $this->get_iter($id);

			$lang_spec_prop = 'content_' . i18n_get_language();

			return !empty($page->data[$lang_spec_prop])
				? $page->get($lang_spec_prop)
				: $page->get('content');
		}

		public function get_title($id)
		{
			$content = $this->get_content($id, $page);

			return preg_match('/\[h1\](.*?)\[\/h1\]/i', $content, $match)
				? $match[1]
				: null;
		}

		public function get_summary($id)
		{
			$content = $this->get_content($id, $page);

			return editable_get_summary($content, $page->get('owner'));
		}

		public function search($query, $limit = null)
		{
			$keywords = parse_search_query($query);

			$text_query = implode(' & ', $keywords);

			$query = sprintf("to_tsvector(content) @@ to_tsquery('%s') OR to_tsvector(content_en) @@ to_tsquery('%1\$s')",
				$this->db->escape_string($text_query));

			return $this->find($query);
		}
	}
