<?php
require_once 'include/init.php';
require_once 'include/search.php';
require_once 'include/controllers/Controller.php';
	
class SearchController extends Controller
{
	protected $providers;

	public function __construct()
	{
		$this->providers = array(
			get_model('DataModelMember'),
			get_model('DataModelEditable'),
			get_model('DataModelCommissie'),
			get_model('DataModelAgenda'),
			get_model('DataModelPhotobook'),
			get_model('DataModelAnnouncement'),
			get_model('DataModelForum'),
			get_model('DataModelWiki'),
		);

		$this->view = View::byName('search', $this);
	}

	protected function _query($query)
	{
		$results = array();

		// Query all providers
		foreach ($this->providers as $provider)
			$results = array_merge($results, $provider->search($query, 10));

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
		if (!empty($_GET['query'])) {
			$query = $_GET['query'];
			$iters = $this->_query($query);

			if (isset($_GET['im_feeling']) && $_GET['im_feeling'] == 'lucky' && count($iters) > 0)
				return $this->view->redirect($iters[0]->get_absolute_url());
		}
		else {
			$query = '';
			$iters = null;
		}

		return $this->view->render_index($query, $iters);
	}
}

$controller = new SearchController();
$controller->run();
