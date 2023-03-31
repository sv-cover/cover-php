<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';


class CommitteeMembersController extends \Controller
{
	protected $view_name = 'committeemembers';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelCommitteeMembers');

		parent::__construct($request, $router);
	}
	
	protected function run_impl()
	{
		if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			&& !get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			throw new \UnauthorizedException();

		$type = isset($_GET['type']) && in_array(intval($_GET['type']), [
			\DataModelCommissie::TYPE_COMMITTEE,
			\DataModelCommissie::TYPE_WORKING_GROUP,
			\DataModelCommissie::TYPE_OTHER])
			? intval($_GET['type'])
			: null;
		
		$iters = $this->model->get_active_members($type);

		return $this->view->render_index($iters);
	}
}
