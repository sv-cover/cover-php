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
		
		$iters = $this->model->get_active_members();		

		return $this->view->render_index($iters);
	}
}

$controller = new ControllerActieveLeden();
$controller->run();
