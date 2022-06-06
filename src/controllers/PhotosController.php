<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/controllers/ControllerCRUD.php';

class PhotosController extends \ControllerCRUD
{
    use PhotoBookRouteHelper;

    protected $view_name = 'photos';
    protected $_var_id = 'photo';

    public function __construct($request, $router)
    {
        $this->model = get_model('DataModelPhotobook');

        parent::__construct($request, $router);
    }

    public function path(string $view, \DataIter $iter = null, bool $json = false)
    {
        $parameters = [
            'photo' => $this->get_photo()->get_id(),
        ];


        if (isset($iter))
        {
            $parameters[$this->_var_id] = $iter->get_id();

            if ($json)
                $parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
        }

        return $this->generate_url('photos', $parameters);
    }

    protected function _read($id)
    {
        return $this->get_photo();
    }

    public function run_read(\DataIter $iter)
    {
        if (!get_policy($iter)->user_can_read($iter))
            throw new \UnauthorizedException('You are not allowed to see this photo.');

        return $this->view->render_photo($this->get_book(), $iter);
    }

    public function run_update(\DataIter $iter)
    {
        if (!get_policy($this->model)->user_can_read($iter->get_book()))
            throw new \UnauthorizedException('You are not allowed to update this photo.');

        if ($this->_form_is_submitted('update', $iter)) {
            $iter->set('beschrijving', $_POST['beschrijving']);
            $this->model->update($iter);
            return $this->view->redirect($this->generate_url('photos', [
                'book' => $this->get_book()->get_id(),
                'photo' => $iter->get_id()
            ]));
        }

        return $this->view->render_update_photo($this->get_book(), $iter, null, []);
    }

    // Compatibility with old views
    public function run_update_photo(\DataIter $iter)
    {
        return $this->run_update($iter);
    }

    public function run_create()
    {
        throw new \NotFoundException();
    }

    public function run_delete(\DataIter $iter)
    {
        throw new \NotFoundException();
    }

    public function run_index()
    {
        throw new \NotFoundException();
    }

    protected function run_impl()
    {
        if (!$this->get_photo())
            throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');
        return parent::run_impl();
    }
}
