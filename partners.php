<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerPartners extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelPartner');

		$this->view = View::byName('partners', $this);
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
}

$controller = new ControllerPartners();
$controller->run();
