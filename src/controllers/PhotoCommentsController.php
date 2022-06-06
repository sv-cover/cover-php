<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/controllers/ControllerCRUD.php';

class PhotoCommentsController extends \ControllerCRUD
{
    use PhotoBookRouteHelper;

    protected $_var_view = 'comment_view';

    protected $_var_id = 'comment_id';

    protected $view_name = 'photocomments';

    public function __construct($request, $router)
    {
        $this->model = get_model('DataModelPhotobookReactie');

        parent::__construct($request, $router);
    }

    public function new_iter()
    {
        $iter = parent::new_iter();
        $iter->set('foto', $this->get_photo()->get_id());
        $iter->set('auteur', get_identity()->get('id'));
        return $iter;
    }

    public function path(string $view, \DataIter $iter = null, bool $json = false)
    {
        $parameters = [];

        if ($json)
            $parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));

        if ($view === 'read')
        {
            $parameters['photo'] = $this->get_photo()['id'];
            $parameters['book'] = $this->get_photo()['scope']['id'];
            return sprintf('%s#comment%d', $this->generate_url('photos', $parameters), $iter->get_id());
        }

        $parameters[$this->_var_view] = $view;
        $parameters['photo'] = $this->get_photo()['id'];
        $parameters['book'] = $this->get_photo()['scope']['id'];
        $parameters['module'] = 'comments';

        if (isset($iter))
            $parameters[$this->_var_id] = $iter->get_id();

        return $this->generate_url('photos', $parameters);
    }

    protected function _index()
    {
        return $this->model->get_for_photo($this->get_photo());
    }

    public function run_likes(\DataIter $iter)
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
                        $iter->like(get_identity()->member());
                        break;
                    case 'unlike':
                        $iter->unlike(get_identity()->member());
                        break;
                }
            } catch (\Exception $e) {
                // Don't break duplicate requests
            }
        }

        if ($response_json)
            return $this->view->render_json([
                'liked' => get_auth()->logged_in() && $iter->is_liked_by(get_identity()->member()),
                'likes' => $iter->get_likes(),
            ]);

        $redirect_url = $this->generate_url('photos', [
            'photo' => $this->get_photo()['id'],
            'book' => $this->get_photo()['scope']['id'],
        ]);

        return $this->view->redirect(sprintf('%s#comment%d', $redirect_url, $iter->get_id()));
    }

    protected function run_impl()
    {
        if (!$this->get_photo())
            throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');
        return parent::run_impl();
    }

    public function set_photo(\DataIterPhoto $photo)
    {
        $this->photo = $photo;
    }
}
