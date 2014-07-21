<?php
include 'include/init.php';
include 'controllers/ControllerCRUD.php';
include 'controllers/ControllerEditable.php';
include 'controllers/ControllerImage.php';

class ControllerBedrijvenLogo extends ControllerImage
{
	protected $format = self::FORMAT_PNG;

	public function __construct($model)
	{
		parent::__construct(null, $model);
	}

	protected function getImage(DataIter $bedrijf)
	{
		$logo = $this->model->get_logo($bedrijf);

		if (!$logo) $this->sendNotFoundResponse();

		return $logo;
	}

	protected function getLastModified(DataIter $bedrijf)
	{
		return strtotime($bedrijf->get('logo_mtime'));
	}
}

class ControllerBedrijvenLogoThumb extends ControllerBedrijvenLogo
{
	protected function getDimensions(Rectangle $original)
	{
		return new Rectangle(200, 200 * $original->height / $original->width);
	}
}

class ControllerBedrijven extends ControllerCRUD
{
	/* protected */ public $model = null;

	protected $query_parameter = 'bedrijf';

	public function __construct()
	{
		parent::__construct();
		
		$this->model = get_model('DataModelBedrijven');

		$this->_register('logo', array(new ControllerBedrijvenLogo($this->model), 'run_image'));

		$this->_register('logo-thumb', array(new ControllerBedrijvenLogoThumb($this->model), 'run_image'));
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

	public function get_image(DataIter $bedrijf)
	{
		return $this->model->get_logo($bedrijf);
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
	$controller = new ControllerBedrijven();
	$controller->run();
}

