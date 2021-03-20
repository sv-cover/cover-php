<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class ControllerCareer extends Controller
{

	public function __construct()
	{
		$this->view = View::byName('career', $this);
	}

    public function run_impl()
    {
        $partners = get_model('DataModelPartner')->find(['has_profile_visible' => 1]);

        usort($partners, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $this->view->render_index($partners);
    }
}

$controller = new ControllerCareer();
$controller->run();
