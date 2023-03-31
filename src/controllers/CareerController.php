<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

class CareerController extends \Controller
{
    protected $view_name = 'career';

    public function run_impl()
    {
        $partners = get_model('DataModelPartner')->find(['has_profile_visible' => 1]);

        usort($partners, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $this->view->render_index($partners);
    }
}
