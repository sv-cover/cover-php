<?php
require_once 'include/init.php';
require_once 'include/search.php';
require_once 'include/controllers/Controller.php';
require_once 'include/view.php';
	
class SearchController extends Controller
{
	protected $providers;

	public function __construct()
	{
		$this->providers = [
			[
				'model' => get_model('DataModelMember'),
				'category_name' => __('leden')
			],
			[
				'model' => get_model('DataModelEditable'),
				'category_name' => __('pagina\'s')
			],
			[
				'model' => get_model('DataModelCommissie'),
				'category_name' => __('commissies')
			],
			[
				'model' => get_model('DataModelAgenda'),
				'category_name' => __('agendapunten')
			],
			[
				'model' => get_model('DataModelPhotobook'),
				'category_name' => __('fotoboeken')
			],
			[
				'model' => get_model('DataModelAnnouncement'),
				'category_name' => __('mededelingen')
			],
			[
				'model' => get_model('DataModelForum'),
				'category_name' => __('forum topics')
			],
			[
				'model' => get_model('DataModelWiki'),
				'category_name' => __('wiki-pagina\'s')
			],
		];

		$this->view = View::byName('search', $this);
	}

	protected function _query($query, array &$errors = [])
	{
		$results = array();

		// Query all providers
		foreach ($this->providers as $provider) {
			try {
				$results = array_merge($results, $provider['model']->search($query, 10));
			} catch (Exception $e) {
				sentry_report_exception($e);
				$errors[] = $provider['category_name'];
			}
		}

		// Filter all results on readability
		$results = array_filter($results, function($result) {
			return get_policy($result)->user_can_read($result);
		});

		// Sort them by relevance
		usort($results, function(SearchResult $a, SearchResult $b) {
			return $a->get_search_relevance() < $b->get_search_relevance();
		});

		return $results;
	}
	
	protected function run_impl()
	{
		$query = '';
		$iters = null;
		$errors = [];

		if (!empty($_GET['query'])) {
			$query = $_GET['query'];
			$iters = $this->_query($query, $errors);

			if (isset($_GET['im_feeling']) && $_GET['im_feeling'] == 'lucky' && count($iters) > 0)
				return $this->view->redirect($iters[0]->get_absolute_url(), false, ALLOW_SUBDOMAINS);
		}
		
		return $this->view->render_index($query, $iters, $errors);
	}
}

$controller = new SearchController();
$controller->run();
