<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'controllers/Controller.php';

class ControllerStickers extends Controller
{
	public function ControllerStickers()
	{
		$this->model = get_model('DataModelStickers');
	}

	public function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array('title' => __('Stickers')));
		run_view('stickers::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}

	public function run()
	{
		if (isset($_POST['action']) && $_POST['action'] == 'add_sticker')
			$this->_add_sticker($_POST);

		else if (isset($_POST['action']) && $_POST['action'] == 'remove_sticker')
			$this->_remove_sticker($_POST);

		$this->run_map();
	}

	public function run_map()
	{
		$stickers = $this->model->get();

		$this->get_content('map', $stickers);
	}

	protected function _add_sticker(array $data)
	{
		if (!logged_in())
			return;

		if (empty($data['label']) || empty($data['lat']) || empty($data['lng']))
			return;

		$id = $this->model->addSticker($data['label'], $data['omschrijving'], $data['lat'], $data['lng']);

		header('Location: stickers.php?sticker=' . $id);
		exit;
	}

	protected function _remove_sticker(array $data)
	{
		if (!member_in_commissie(COMMISSIE_BESTUUR))
			return;

		$iter = new DataIter($this->model, $data['id'], array());

		$this->model->delete($iter);

		header('Location: stickers.php');
		exit;
	}
}

$controller = new ControllerStickers;
$controller->run();
