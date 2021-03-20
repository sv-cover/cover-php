<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class PartnersController extends \ControllerCRUD
{
    protected $view_name = 'partners';

    public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPartner');

        parent::__construct($request, $router);
	}

    protected function _index()
    {
        $iters = parent::_index();

        usort($iters, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $iters;
    }

    public function run_autocomplete()
    {
        $partners = $this->model->find(['name__contains' => $_GET['search']]);

        $data = [];

        foreach ($partners as $partner)
            $data[] = [
                'id' => $partner['id'],
                'name' => $partner['name'],
            ];

        return $this->view->render_json($data);
    }

    public function run_index()
    {
        if (!get_policy($this->model)->user_can_update($this->new_iter()))
            return $this->view->redirect($this->generate_url('career'));
        return parent::run_index();
    }

    public function run_preview()
    {
        return markup_parse($_POST['profile']);
    }
}
