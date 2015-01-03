<?php
	require_once 'include/init.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerHomepage extends Controller
	{
		protected function _get_title($iters = null)
		{
			return __('Homepage');
		}

		protected function _process_language()
		{
			ob_end_clean();

			$language = get_post('language');
			
			if (!i18n_valid_language($language))
				$this->redirect('index.php');

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

			$this->redirect($return_path);
		}
		
		protected function run_impl()
		{
			if (isset($_POST['submindexlanguage']))
				$this->_process_language();
			else
				$this->get_content('homepage');
		}		
	}
	
	$controller = new ControllerHomepage();
	$controller->run();
