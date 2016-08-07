<?php
	require_once 'include/init.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerHomepage extends Controller
	{
		public function __construct()
		{
			$this->view = View::byName('homepage', $this);
		}
			
		protected function _process_language()
		{
			while (ob_get_level() > 0 && ob_end_clean());

			if (!isset($_POST['language']))
				throw new Exception('Language parameter missing');

			$language = $_POST['language'];
			
			if (!i18n_valid_language($language)) {
				header('Location: index.php');
				exit();
			}

			$member_data = logged_in();
			
			if ($member_data) {
				/* Set language in profile of member */
				$model = get_model('DataModelMember');
				$iter = $model->get_iter($member_data['id']);
				
				$iter->set('taal', $language);
				$model->update_profiel($iter);
			} else {
				/* Set language in session */
				$_SESSION['taal'] = $language;
			}
			
			$return_path = isset($_POST['return_to'])
				? $_POST['return_to']
				: 'index.php';

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
	
	$controller = new ControllerHomepage();
	$controller->run();
