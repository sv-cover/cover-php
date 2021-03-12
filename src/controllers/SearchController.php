<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/search.php';
require_once 'include/controllers/Controller.php';
require_once 'include/view.php';
	
class SearchController extends \Controller
{
	protected $providers;

	public function __construct()
	{
		$this->providers = [
			[
				'model' => get_model('DataModelMember'),
				'category_name' => __('members')
			],
			[
				'model' => get_model('DataModelEditable'),
				'category_name' => __('pages')
			],
			[
				'model' => get_model('DataModelCommissie'),
				'category_name' => __('committees')
			],
			[
				'model' => get_model('DataModelAgenda'),
				'category_name' => __('calendar events')
			],
			[
				'model' => get_model('DataModelPhotobook'),
				'category_name' => __('photo books')
			],
			[
				'model' => get_model('DataModelAnnouncement'),
				'category_name' => __('announcements')
			],
			[
				'model' => get_model('DataModelForum'),
				'category_name' => __('forum topics')
			],
			[
				'model' => get_model('DataModelWiki'),
				'category_name' => __('wiki pages')
			],
		];

		$this->view = \View::byName('search', $this);
	}

	protected function _query($query, array &$errors = [], array &$timings = [])
	{
		$results = array();

		// Query all providers
		foreach ($this->providers as $provider) {
			try {
				$start = microtime(true);
				$results = array_merge($results, $provider['model']->search($query, 10));
				$timings[$provider['category_name']] = microtime(true) - $start;
			} catch (\Exception $e) {
				sentry_report_exception($e);
				$errors[] = $provider['category_name'];
			}
		}

		$start = microtime(true);

		// Filter all results on readability
		$results = array_filter($results, function($result) {
			return get_policy($result)->user_can_read($result);
		});

		$timings['_filtering'] = microtime(true) - $start;

		$start = microtime(true);

		// Sort them by relevance
		usort($results, function(\SearchResult $a, \SearchResult $b) {
			return $a->get_search_relevance() < $b->get_search_relevance();
		});

		$timings['_sorting'] = microtime(true) - $start;

		return $results;
	}
	
	protected function run_impl()
	{
		$query = '';
		$query_parts = [];
		$results = null;
		$errors = [];
		$timings = [];

		if (!empty($_GET['query'])) {
			$query = iconv('UTF-8', 'UTF-8//IGNORE', $_GET['query']); // Remove invalid character points
			$query_parts = parse_search_query($query);
			$results = $this->_query($query, $errors, $timings);

			if (isset($_GET['im_feeling']) && $_GET['im_feeling'] == 'lucky' && count($results) > 0)
				return $this->view->redirect($results[0]->get_absolute_url(), false, ALLOW_SUBDOMAINS);
		}

		return $this->view->render('index.twig', compact('query', 'query_parts', 'results', 'errors', 'timings'));
	}
}
