<?php
include 'include/init.php';
include 'controllers/Controller.php';
include 'controllers/ControllerEditable.php';

class ControllerBedrijven extends Controller
{
	/* private */ public $model = null;

	public function __construct()
	{
		$this->model = get_model('DataModelBedrijven');
	}
	
	/* protected */ function get_content($view, $iter = null, $params = null) {
		$this->run_header(array('title' => __('Bedrijven')));
		run_view($view, $this->model, $iter, $params);
		$this->run_footer();
	}

	protected function get_page_content(DataIter $bedrijf)
	{
		$this->run_header(array('title' => $bedrijf->get('naam')));
		$editable = new ControllerEditable($bedrijf->get('page'));
		$editable->run();
		$this->run_footer();
	}

	protected function _process_update_bedrijf($bedrijf, $data)
	{
		//
	}

	protected function _process_add_bedrijf($bedrijf, $data)
	{
		//
	}
	
	/* protected */ function run_impl()
	{
		if (!empty($_GET['bedrijf'])) {
			$bedrijf = $this->model->get_from_name($_GET['bedrijf']);
			
			if (!$bedrijf)
				return $this->get_content('common::not_found');
			
			if ($_SERVER['REQUEST_METHOD'] == 'POST')
				$this->_process_update_bedrijf($bedrijf, $_POST);

			return $this->get_page_content($bedrijf);
		} else if(isset($_GET['view']) && $_GET['view'] == 'add-bedrijf') {
			if ($_SERVER['REQUEST_METHOD'] == 'POST')
				$this->_process_add_bedrijf($_POST);

			$this->get_content('bedrijven::add_bedrijf');
		} else {
			$bedrijven = $this->model->get();
			$this->get_content('bedrijven::bedrijven', $bedrijven);
		}
	}
}

$controller = new ControllerBedrijven();
$controller->run();
