<?php
	ini_set('display_errors', true);
	error_reporting(E_ALL ^ E_NOTICE);

	include('include/init.php');
	require_once('controllers/Controller.php');
	require_once('controllers/ControllerEditable.php');
	require_once('controllers/ControllerNews.php');
	
	class ControllerHomepage extends Controller {
		function ControllerHomepage() {
			parent::Controller('homepage');
		}
		
		function get_content() {
			$this->run_header(Array('menu' => $this->view, 'title' => ucfirst($this->view)));

			run_view($this->view, $this->model, $this->iter, $this->params);
			
			$editable = new ControllerEditable('Startpagina');
			$editable->run();

			$news = new ControllerNews();
			$news->run();
	
			$this->run_footer();
		}
		
		function _process_language() {
			ob_end_clean();

			$language = get_post('language');
			
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
			
			header('Location: index.php');
			exit();
		}
		
		function run_impl() {
			if (isset($_POST['submindexlanguage']))
				$this->_process_language();
			else
				$this->get_content();
		}		
	}
	
	$controller = new ControllerHomepage();
	$controller->run();
?>
