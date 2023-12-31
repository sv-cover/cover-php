<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/framework/search.php';
require_once 'src/framework/router.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterEditable extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
			'committee_id',
			'titel',
            'slug',
			'content', // NL, not used anymore
			'content_en',
			'content_de', // not used anymore
			'cover_image_url',
			'last_modified'
		];
	}

	public function get_cover_image($width=null)
	{
		return get_filemanager_url($this['cover_image_url'], $width);
	}

	public function get_cover_image_orientation()
	{
		$filemanager_root = get_config_value('filemanager_root', 'https://filemanager.svcover.nl');
		$resize_exts = get_config_value('filemanager_image_resize_extensions', ['jpg', 'jpeg', 'png']);

		if (empty($this['cover_image_url']) || !in_array(pathinfo($this['cover_image_url'], PATHINFO_EXTENSION), $resize_exts))
			return false; // Can't determine size

		$result = file_get_contents(sprintf('%s/images/size?f=%s', $filemanager_root, urlencode($this['cover_image_url'])));
		try {
			$result = json_decode($result);
			$width = $result->width;
			$height = $result->height;
		} catch (\Exception $e) {
			return false;
		}

		if ($width == $height)
			return 'square';
		if ($width > $height)
			return 'landscape';
		else
			return 'portrait';
	}

	public function get_committee()
	{
		return get_model('DataModelCommissie')->get_iter($this->data['committee_id']);
	}

	public function get_locale_content($language = null)
	{
		if (!$language && $this->has_value('search_language'))
			$language = $this['search_language'];

		if (!$language)
			$language = i18n_get_language();

		$preferred_fields = $language == 'en'
			? array('content_en', 'content')
			: array('content', 'content_en');

		foreach ($preferred_fields as $field)
			if ($this->has_field($field) && $this->get($field) != '')
				return $this->get($field);

		return null;
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
		return normalize_search_rank($this->data['search_relevance']);
	}

	public function get_search_type()
	{
		return 'page';
	}

	public function get_absolute_path($url = false)
	{
		$router = get_router();
		$reference_type = $url ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $router->generate('page', ['id' => $this->get_id()], $reference_type);
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

    public function get_iter_from_slug($slug)
    {
        return $this->find_one(['slug' => $slug]);
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
		$preferred_language = i18n_get_language();

		$weights = [];

		foreach (['en', 'nl'] as $language)
			$weights[$language] = $preferred_language == $language ? 1.0 : 0.9;

		$language_table = implode(',',
			array_map(
				function($lang, $weight) {
					return sprintf("('%s', %f)", $lang, $weight);
				},
				array_keys($weights),
				array_values($weights)));

		$query = "
			WITH
				-- Use weights as tiebreakers when results from both languages match equally well
				weights AS (
					SELECT
						v.*
					FROM (VALUES $language_table) as v (language, weight)
				),
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
					LEFT JOIN weights w ON
						w.language = s.search_language
					LEFT JOIN search_results s2 ON
						s.id = s2.id
					LEFT JOIN weights w2 ON
						w2.language = s2.search_language
					WHERE
						-- Find the result with the largest weighted search relevance
						w.weight * s.search_relevance > w2.weight * s2.search_relevance
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

	public function insert(DataIter $iter)
	{
		$iter['last_modified'] = new DateTime();

		return parent::insert($iter);
	}

	public function update(DataIter $iter)
	{
		$iter['last_modified'] = new DateTime();

		return parent::update($iter);
	}
}