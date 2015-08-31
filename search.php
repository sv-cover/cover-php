<?php
require_once 'include/init.php';
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
			get_model('DataModelFotoboek'),
			get_model('DataModelAnnouncement')
		);
	}

	protected function get_content($view, $iters = null, $params = null)
	{
		$this->run_header(array('title' => __('Zoeken')));
		run_view('search::' . $view, $this->model, $iters, $params);
		$this->run_footer();
	}

	protected function _run_query($query)
	{
		$results = array();

		foreach ($this->providers as $provider)
			$results = array_merge($results, $provider->search($query, 10));

		usort($results, function(SearchResult $a, SearchResult $b) {
			return $a->get_search_relevance() < $b->get_search_relevance();
		});

		return $results;
	}
	
	function run_impl()
	{
		if (!empty($_GET['query'])) {
			$query = $_GET['query'];
			$iters = $this->_run_query($query);

			if (isset($_GET['im_feeling']) && $_GET['im_feeling'] == 'lucky' && count($iters) > 0)
				return $this->redirect($iters[0]->get_absolute_url());
		}
		else {
			$query = '';
			$iters = null;
		}

		$this->get_content('index', $iters, compact('query'));
	}
}

$controller = new SearchController();
$controller->run();
