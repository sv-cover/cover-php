<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class ControllerActieveLeden extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelActieveLeden');

		$this->view = View::byName('actieveleden', $this);
	}
	
	protected function run_impl()
	{
		if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			&& !get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			throw new UnauthorizedException();

		$type = isset($_GET['type']) && in_array(intval($_GET['type']), [
			DataModelCommissie::TYPE_COMMITTEE,
			DataModelCommissie::TYPE_WORKING_GROUP,
			DataModelCommissie::TYPE_OTHER])
			? intval($_GET['type'])
			: null;
		
		$iters = $this->model->get_active_members($type);

		return $this->view->render_index($iters);
	}
}

$controller = new ControllerActieveLeden();
$controller->run();
