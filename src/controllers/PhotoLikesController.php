<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';


class PhotoLikesController extends \Controller
{
    public function __construct(\DataIterPhoto $photo, $request, $router)
    {
        $this->photo = $photo;

        $this->model = get_model('DataModelPhotobookLike');

        parent::__construct($request, $router);
    }

    public function run()
    {
        $action = null;
        $response_json = false;

        if ($_SERVER["CONTENT_TYPE"] === 'application/json')
        {
            $response_json = true;
            $json = file_get_contents('php://input');
            $data = json_decode($json);
            if (isset($data->action))
                $action = $data->action;
        }
        elseif (isset($_POST['action']))
            $action = $_POST['action'];

        if (get_auth()->logged_in() && isset($action))
        {
            try {
                switch ($action) {
                    case 'like':
                        $this->model->like($this->photo, get_identity()->get('id'));
                        break;
                    case 'unlike':
                        $this->model->unlike($this->photo, get_identity()->get('id'));
                        break;
                }
            } catch (\Exception $e) {
                // Don't break duplicate requests
            }
        }

        if ($response_json)
            return $this->view->render_json([
                'liked' => get_auth()->logged_in() && $this->model->is_liked($this->photo, get_identity()->get('id')),
                'likes' => count($this->model->get_for_photo($this->photo))
            ]);

        return $this->view->redirect($this->generate_url('photos', [
            'photo' => $this->photo['id'],
            'book' => $this->photo['scope']['id'],
        ]));
    }
}
