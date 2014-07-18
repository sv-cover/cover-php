<?php
include 'include/init.php';
include 'controllers/ControllerCRUD.php';
include 'controllers/ControllerEditable.php';

class ControllerBedrijven extends ControllerCRUD
{
	/* protected */ public $model = null;

	protected $query_parameter = 'bedrijf';

	public function __construct()
	{
		parent::__construct();
		
		$this->model = get_model('DataModelBedrijven');
	}
	
	/* protected */ function get_content($view, $iter = null, $params = null) {
		$this->run_header(array(
			'title' => $iter instanceof DataIter
				? $iter->get('naam') 
				: __('Bedrijven')));

		run_view('bedrijven::' . $view, $this->model, $iter, $params);
		
		$this->run_footer();
	}

	protected function _create(array $data, array &$errors)
	{
		$data = $this->model->validate($data, $errors);

		if (count($errors))
			return false;

		if (!($bedrijf = parent::_create($data, $errors)))
			return false;

		if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
			$fh = fopen($_FILES['logo']['tmp_name'], 'rb');
			$this->model->set_logo($bedrijf, $fh);
			fclose($fh);
		}

		return $bedrijf;
	}

	protected function _update(DataIter $bedrijf, array $data, array &$errors)
	{
		$data = $this->model->validate($data, $errors);

		if (count($errors))
			return false;

		if (!parent::_update($bedrijf, $data, $errors))
			return false;

		if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
			$fh = fopen($_FILES['logo']['tmp_name'], 'rb');
			$this->model->set_logo($bedrijf, $fh);
			fclose($fh);
		}

		return true;
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
	$controller = new ControllerBedrijven();
	$controller->run();
}

