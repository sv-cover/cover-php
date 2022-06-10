<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/controllers/Controller.php';

class PhotoPrivacyController extends \Controller
{
    use PhotoBookRouteHelper;

    protected $view_name = 'photos';

    public function __construct($request, $router)
    {
        $this->model = get_model('DataModelPhotobookPrivacy');

        parent::__construct($request, $router);
    }

    protected function run_impl()
    {

        if (!get_auth()->logged_in())
            throw new \UnauthorizedException();

        $member = get_identity()->member();

        $photo =$this->get_photo();
        if (!$photo)
            throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');

        $response = array();

        if ($this->_form_is_submitted('privacy', $photo)) {
            if ($_POST['visibility'] == 'hidden')
                $this->model->mark_hidden($photo, $member);
            else
                $this->model->mark_visible($photo, $member);

            return $this->view->redirect($this->generate_url('photos.book.photo', [
                'photo' => $photo['id'],
                'book' => $photo['scope']['id'],
            ]));
        }
        
        return $this->view->render_privacy($photo, $this->model->is_visible($photo, $member) ? 'visible' : 'hidden');
    }
}
