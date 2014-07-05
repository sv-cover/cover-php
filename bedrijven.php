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
		$this->run_header(array(
			'title' => $iter instanceof DataIter
				? $iter->get('naam') 
				: __('Bedrijven')));

		run_view($view, $this->model, $iter, $params);
		
		$this->run_footer();
	}

	protected function get_page_content(DataIter $bedrijf)
	{
		$editable = new ControllerEditable($bedrijf->get('page'));
		$this->get_content('bedrijven::show', $bedrijf, compact('editable'));
	}

	protected function _process_update_bedrijf($bedrijf, $data, array &$errors)
	{
		//
	}

	protected function _process_add_bedrijf($data, array &$errors)
	{
		if (empty($data['naam'])) {
			$errors[] = 'naam';
			return null;
		}

		$bedrijf = new DataIter($this->model, null, array(
			'naam' => trim($data['naam']),
			'website' => trim($data['website'])
		));

		if (!($id = $this->model->insert($bedrijf, true)))
			return null;
		
		$bedrijf = new DataIter($this->model, $id, $bedrijf->data);

		if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
			$fh = fopen($_FILES['logo']['tmp_name'], 'rb');
			$this->model->set_logo($bedrijf, $fh);
			fclose($fh);
		}

		return $bedrijf;
	}

	protected function _redirect_to_bedrijf(DataIter $bedrijf)
	{
		$location = 'bedrijven.php?bedrijf=' . rawurlencode($bedrijf->get('slug'));
		header('Location: ' . $location);
		printf('<a href="%s">Continue to %s</a>', htmlspecialchars($location, ENT_QUOTES), htmlspecialchars($bedrijf->get('naam')));
	}
	
	/* protected */ function run_impl()
	{
		$bedrijf = null;
		$data = null;
		$errors = array();

		if (!empty($_GET['bedrijf'])) {
			if (($bedrijf = $this->model->get_from_name($_GET['bedrijf'])) === null)
				return $this->get_content('common::not_found');
			
			$data = $bedrijf->data;	
		}

		if (isset($_GET['view']))
			$view = $_GET['view'];
		else if ($bedrijf)
			$view = 'show';
		else
			$view = 'index';

		switch ($view)
		{
			case 'edit':
				if ($_SERVER['REQUEST_METHOD'] == 'POST')
					if ($this->_process_update_bedrijf($bedrijf, $_POST, $errors))
						return $this->_redirect_to_bedrijf($bedrijf);
				
				$this->get_content('bedrijven::edit', $bedrijf, compact('data', 'errors'));
				break;
		
			case 'add':
				if ($_SERVER['REQUEST_METHOD'] == 'POST')
					if ($bedrijf = $this->_process_add_bedrijf($_POST, $errors))
						return $this->_redirect_to_bedrijf($bedrijf);
				
				$this->get_content('bedrijven::add', $bedrijf, compact('data', 'errors'));
				break;

			case 'show':
				$this->get_page_content($bedrijf);
				break;

			default:
				$bedrijven = $this->model->get();
				$this->get_content('bedrijven::index', $bedrijven);
				break;
		}
	}
}

$controller = new ControllerBedrijven();
$controller->run();
