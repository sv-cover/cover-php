<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

class HomepageController extends \Controller
{
	protected $view_name = 'homepage';
		
	protected function _process_language()
	{
		if (isset($_POST['language']) && i18n_valid_language($_POST['language']))
		{
			$language = $_POST['language'];
			
			$member_data = get_identity()->member();
			
			if ($member_data) {
				/* Set language in profile of member */
				$model = get_model('DataModelMember');
				$iter = $model->get_iter($member_data['id']);
				
				$iter->set('taal', $language);
				$model->update($iter);
			} else {
				/* Set language in session */
				$_SESSION['taal'] = $language;
			}
		}
		
		$return_path = isset($_POST['return_to'])
			? $_POST['return_to']
			: $this->generate_url('homepage');

		return $this->view->redirect($return_path);
	}
	
	protected function run_impl()
	{
		if (isset($_POST['submindexlanguage']))
			return $this->_process_language();
		else
			return $this->view->render_homepage();
	}		
}
