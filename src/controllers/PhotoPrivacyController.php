<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

class PhotoPrivacyController extends \Controller
{
    protected $photo;

    protected $view_name = 'photos';

    public function __construct(\DataIterPhoto $photo, $request, $router)
    {
        $this->photo = $photo;

        $this->model = get_model('DataModelPhotobookPrivacy');

        parent::__construct($request, $router);
    }

    protected function run_impl()
    {
        if (!get_auth()->logged_in())
            throw new \UnauthorizedException();

        $member = get_identity()->member();

        $response = array();

        if ($this->_form_is_submitted('privacy', $this->photo)) {
            if ($_POST['visibility'] == 'hidden')
                $this->model->mark_hidden($this->photo, $member);
            else
                $this->model->mark_visible($this->photo, $member);

            return $this->view->redirect($this->generate_url('photos', [
                'photo' => $this->photo['id'],
                'book' => $this->photo['scope']['id'],
            ]));
        }
        
        return $this->view->render_privacy($this->photo, $this->model->is_visible($this->photo, $member) ? 'visible' : 'hidden');
    }
}
