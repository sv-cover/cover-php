<?php
require_once 'include/search.php';
require_once 'include/data/DataModel.php';
require_once 'include/models/DataModelCommissie.php';

class DataIterPartner extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
		    'name',
		    'type',
		    'url',
		    'logo_url',
		    'logo_dark_url',
		    'profile',
		    'has_banner_visible',
		    'has_profile_visible',
		    'created_on',
		];
	}

	static public function rules()
	{
		return [
			'name' => [
				'required' => true,
				'validate' => ['not_empty'],
			],
			'type' => [
				'required' => true,
				'clean' => 'intval',
				'validate' => [
					'not_empty',
					function($type) {
						return in_array($type, [
							DataModelPartner::TYPE_SPONSOR,
							DataModelPartner::TYPE_MAIN_SPONSOR,
							DataModelPartner::TYPE_OTHER,
						]);
					}
				],
			],
			'url' => [
				'required' => true,
				'validate' => [
					function($url) {
						return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
					}
				],
			],
			'logo_url' => [
				'required' => true,
				'validate' => ['filemanger_file'],
			],
			'logo_dark_url' => [
				'clean' => 'clean_empty',
				'validate' => ['optional', 'filemanger_file'],
			],
			'profile' => [
				'clean' => 'clean_empty',
			],
			'has_banner_visible' => [
				'is_checkbox' => true,
				'clean' => 'clean_checkbox',
			],
			'has_profile_visible' => [
				'is_checkbox' => true,
				'clean' => 'clean_checkbox',
			],
		];
	}

	public function get_search_relevance()
	{
		return 0.5;
	}
	
	public function get_search_type()
	{
		return 'vacancy';
	}

	public function get_absolute_url()
	{
		return sprintf('partners.php?view=read&id=%d', $this->get_id());
	}

	public function get_logo($width=null)
	{
		return get_filemanager_url($this['logo_url'], $width);
	}

	public function get_logo_dark($width=null)
	{
		return get_filemanager_url($this['logo_dark_url'], $width);
	}

	public function get_vacancies()
	{
		return get_model('DataModelVacancy')->find(['partner_id' => $this->get_id()]);
	}

	public function get_sort_order()
	{
		switch ($this['type'] ?? DataModelPartner::TYPE_SPONSOR)
		{
			case DataModelPartner::TYPE_MAIN_SPONSOR:
				return 0;
			case DataModelPartner::TYPE_SPONSOR:
				return 1;
			default:
				return 2;
		}
	}
}

class DataModelPartner extends DataModel implements SearchProvider
{
	const TYPE_SPONSOR = 0;
	const TYPE_MAIN_SPONSOR = 1;
	const TYPE_OTHER = 2;

	public $dataiter = 'DataIterPartner';

	public function __construct($db)
	{
		parent::__construct($db, 'partners');
	}

	public function update(DataIter $iter)
	{
		$iter['last_modified'] = new DateTime();

		return parent::update($iter);
	}

	public function search($query, $limit = null)
	{
		return [];
	}

	public function shuffle(&$iters)
	{
		// Shuffle the banners
		shuffle($iters);

		usort($iters, function($a, $b) {
			return $a['sort_order'] <=> $b['sort_order'];
		});
	}
}
