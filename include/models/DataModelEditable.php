<?php
	require_once 'include/data/DataModel.php';
	require_once 'include/search.php';

	class DataIterEditable extends DataIter implements SearchResult
	{
		static $content_fields = [
			'en' => 'content_en',
			'nl' => 'content'
		];

		static public function fields()
		{
			return [
				'id',
				'committee_id',
				'titel',
				'content',
				'content_en',
				'content_de', // not used anymore
			];
		}

		public function get_locale($language = null)
		{
			if (!$language && $this->has_value('search_language'))
				$language = $this['search_language'];

			if (!$language)
				$language = i18n_get_language();

			$preferred_fields = $language == 'en'
				? array('en', 'nl')
				: array('nl', 'en');

			foreach ($preferred_fields as $lang)
				if ($this->has_field(self::$content_fields[$lang]) && $this->get(self::$content_fields[$lang]) != '')
					return $lang;

			return null;
		}

		public function get_locale_content($language = null)
		{
			$lang = $this->get_locale($language);
			return $lang ? $this->get(self::$content_fields[$lang]) : null;
		}

		public function get_title($language = null)
		{
			$content = $this->get_locale_content($language);

			return preg_match('/\[h1\](.+?)\[\/h1\]\s*/ism', $content, $match)
				? $match[1]
				: $this->get('titel');
		}

		public function get_summary($language = null)
		{
			$content = $this->get_locale_content($language);

			if (preg_match('/\[samenvatting\](.+?)\[\/samenvatting\]/msi', $content, $matches))
				return markup_strip($matches[1]);

			return $summary = summarize(markup_strip($content), 128);
		}

		public function get_search_relevance()
		{
			return normalize_search_rank($this->get('search_relevance'));
		}

		public function get_search_type()
		{
			return 'page';
		}

		public function get_absolute_url()
		{
			return sprintf('show.php?id=%d', $this->get_id());
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
			return $this->find_one(['titel' => $title]);
		}

		public function get_content($id)
		{
			return $this->get_iter($id)->get_locale_content();
		}

		public function get_title($id)
		{
			return $this->get_iter($id)->get_title();
		}

		public function get_summary($id)
		{
			return $this->get_iter($id)->get_summary();
		}

		public function search($search_query, $limit = null)
		{
			$query = "
				WITH
					search_results AS (
						SELECT
							id,
							ts_rank_cd(to_tsvector('english', content_en), query) as search_relevance,
							'en' as search_language
						FROM
							{$this->table},
							plainto_tsquery('english', :query) query
						WHERE
							to_tsvector('english', content_en) @@ query
					UNION
						SELECT
							id,
							ts_rank_cd(to_tsvector(content), query) as search_relevance,
							'nl' as search_language
						FROM
							{$this->table},
							plainto_tsquery('dutch', :query) query
						WHERE
							to_tsvector('dutch', content) @@ query
					),
					unique_search_results AS (
						SELECT
							s.id,
							s.search_relevance,
							s.search_language
						FROM
							search_results s
						LEFT JOIN search_results s2 ON
							s.id = s2.id
							AND s2.search_relevance > s.search_relevance
						WHERE
							s2.id IS NULL
					)
				SELECT
					p.*,
					s.*
				FROM
					unique_search_results s
				LEFT JOIN {$this->table} p ON
					p.id = s.id
				ORDER BY
					s.search_relevance DESC
			";

			if ($limit !== null)
				$query .= sprintf(" LIMIT %d", $limit);

			$rows = $this->db->query($query, false, [':query' => $search_query]);
			$iters = $this->_rows_to_iters($rows);

			$keywords = parse_search_query($search_query);
			
			$pattern = sprintf('/(%s)/i', implode('|', array_map(function($p) { return preg_quote($p, '/'); }, $keywords)));

			// Enhance search relevance score when the keywords appear in the title of a page :D
			foreach ($iters as $iter)
			{
				$keywords_in_title = preg_match_all($pattern, $iter->get_title('nl'))
				                   + preg_match_all($pattern, $iter->get_title('en'));

				$iter->set('search_relevance', $iter->get('search_relevance') + $keywords_in_title);
			}

			return $iters;
		}
	}