<?php
	if (!defined('IN_SITE'))
		return;

	require_once('include/controllers/Controller.php');
	require_once('include/editable.php');

	/** 
	  * A class implementing the editable page controller. This class 
	  * is a full controller for editable pages and can be used by other
	  * controllers to embed an editable page. The embedding is fully
	  * automated and the controller embedding this controller only
	  * needs to instantiate and run a #ControllerEditable (great!)
	  */
	class ControllerEditable extends Controller
	{
		public $page;

		/**
		  * ControllerEditable constructor
		  * @id the id (or title) of the editable page
		  */
		function ControllerEditable($id) {
			$this->model = get_model('DataModelEditable');
			
			if ($id instanceof DataIterEditable)
				$this->page = $id;
			elseif (ctype_digit($id) || is_int($id))
				$this->page = $this->model->get_iter($id);
			else {
				$this->page = $this->model->get_iter_from_title($id);

				if (!$this->page)
					$this->page = new DataIterEditable($this->model, -1, array('titel' => $id));
			}
		}
		
		/**
		  * Get the editable page content. This function does
		  * not run the header or the footer view since the class
		  * is meant to be embedded.
		  * @view the editable::view to get
		  * @iter the iter to pass on to the view
		  * @params optional; the params to pass on to the view
		  */
		function get_content($view, $iter, $params = null) {
			run_view('editable::' . $view, $this->model, $iter, $params);
		}
		
		function _get_language() {
			if (isset($_POST['editable_language']) && i18n_valid_language($_POST['editable_language']))
				return $_POST['editable_language'];
			elseif (isset($_GET['editable_language']) && i18n_valid_language($_GET['editable_language']))
				return $_GET['editable_language'];
			else
				return i18n_get_language();
		}
		
		function _get_content_field() {
			$language = $this->_get_language();

			if ($language == 'nl')
				return 'content';
			else
				return 'content_' . $language;
		}

		function _user_can_edit(DataIter $iter)
		{
			return member_in_commissie($iter->get('owner')) || member_in_commissie(COMMISSIE_BESTUUR);
		}
		
		/**
		  * Function that performs common preprocessing before actions
		  * like add, delete save. The function checks if the
		  * currently logged in member has write access to the page
		  * , parses the page number and splits the pages
		  * @iter a #DataIter of the editable page
		  * @page reference; location of the page content
		  *
		  * @result true if all preprocessing went fine, false
		  * otherwise. This function already gets the content for
		  * the view showing the error when it returns false
		  */
		function _page_prepare($iter, &$page, &$field) {
			if (!$this->_user_can_edit($iter)) {
				if (isset($_GET['xmlrequest'])) {
					ob_end_clean();
				
					echo __('Deze pagina kan niet door jou worden bewerkt.');
					exit();
				}

				$this->get_content('read_only');
				return false;
			}

			$language = $this->_get_language();
			$field = $this->_get_content_field();
			
			/* Just being paranoid here */
			if (!in_array($field, array_keys($iter->data))) {
				if (isset($_GET['xmlrequest'])) {
					ob_end_clean();
					
					echo __('Er zit iets niet goed met de taalinstelling. Neem contact op met de WebCie');
					exit();
				}
				
				$this->get_content('something_went_wrong', null, array('message' => sprintf(__('Er zit iets niet goed met de taalinstelling. Neem contact op met %s'), '<a href="mailto:webcie@ai.rug.nl">' . __('de Webcie') . '</a>')));
				return false;
			}

			$page = $iter->get($field);

			return true;	
		}
		
		function _prepare_mail($iter, $page) {
			$data = $iter->data;
			$data['member_naam'] = member_full_name(null, IGNORE_PRIVACY);
			$data['page'] = $page;
			$data['taal'] = $this->_get_language();
			
			$commissie_model = get_model('DataModelCommissie');

			$inbestuur = member_in_commissie(COMMISSIE_BESTUUR, false);
			$incommissie = member_in_commissie($iter->get('owner'), false);
			
			if (!$incommissie && $inbestuur) {
				/* Bestuur changed something, notify commissie */
				$data['commissie_naam'] = $commissie_model->get_naam(COMMISSIE_BESTUUR);
				//$data['email'] = array($commissie_model->get_email($iter->get('owner')));
			} elseif (!$inbestuur && $incommissie) {
				/* Commissie changed something, notify bestuur */
				$data['commissie_naam'] = $commissie_model->get_naam($iter->get('owner'));
				$data['email'] = array(get_config_value('email_bestuur'));
			} else {
				/* Easy changed something, notify bestuur and commissie */
				$data['commissie_naam'] = $commissie_model->get_naam(COMMISSIE_EASY);
				$data['email'] = array(//$commissie_model->get_email($iter->get('owner')),
							get_config_value('email_bestuur'));
			}
			
			return $data;		
		}
		
		/**
		  * Saves an editable page
		  * @iter a #DataIter of the editable page
		  */
		function _do_save($iter) {
			if (!$this->_page_prepare($iter, $page, $field))
				return;
			
			$page = false;
						
			if (!$page)
				$page = get_post($field);

			$iter->set($field, $page);
			$success = $this->model->update($iter);

			if ($success !== null)
			{
				$data = $this->_prepare_mail($iter, get_post($field));
				
				if ($data['email']) {
					$body = parse_email('editable_edit.txt', $data);
					$subject = 'Pagina ' . $data['titel'] . ' gewijzigd';

					foreach ($data['email'] as $email)
						mail($email, $subject, $body, "From: webcie@ai.rug.nl\r\n");
				}
			}

			if (isset($_GET['xmlrequest']))
			{
				ob_end_clean();
				
				if ($success !== null)
					printf(__('De pagina %s is opgeslagen.'), $iter->get('titel'));
				else
					echo $this->model->db->get_last_error();

				exit();
			}

			if ($success !== null)
				$_SESSION['alert'] = sprintf(__('De pagina %s is opgeslagen.'), $iter->get('titel'));
			else
				$_SESSION['alert'] = $this->model->db->get_last_error();

			header('Location: ' . add_request(get_request(), 'editable_edit=' . $iter->get('id') . '&editable_language=' . $this->_get_language()));
			exit();
		}
		
		/**
		  * Views the editable page in edit mode
		  * @iter a #DataIter of the editable page
		  */
		function _view_edit($iter) {
			$params = array('language' => $this->_get_language());

			if (!$this->_user_can_edit($iter))
				$this->get_content('read_only', $iter, $params);
			else
				$this->get_content('edit', $iter, $params);
		}
		
		function _view_editable($page) {
			$language = $this->_get_language();
			$field = $this->_get_content_field();

			// fall back to the other language if this language's content is empty
			if (!$page->get($field)) {
				$language = $language == 'nl' ? 'en' : 'nl';
				$field = $language == 'nl' ? 'content' : 'content_en';
			}

			$content = editable_parse($page->get($field), $page->get('owner'));
			
			$params = array('page' => $content, 'language' => $language, 'field' => $field);
			
			$this->get_content('editable', $page, $params);
		}
		
		/**
		  * Runs the editable page. This function will handle all
		  * the actions (add, delete, edit, show) of the editable
		  * page transparently to the controller embedding the
		  * page
		  */
		function run() {
			if (!$this->page) {
				if (isset($_GET['xmlrequest'])) {
					ob_end_clean();
				
					echo __('Deze pagina bestaat niet...');
					exit();
				}
				
				$this->get_content('editable', $this->page);
			} elseif (isset($_POST['submeditable']) && $_POST['submeditable'] == $this->page->get('id'))
				$this->_do_save($this->page);
			elseif (isset($_GET['editable_edit']) && $_GET['editable_edit'] == $this->page->get('id'))
				$this->_view_edit($this->page);
			else
				$this->_view_editable($this->page);
		}

		public function get_title()
		{
			return $this->page->get_title($this->_get_language());
		}

		public function user_can_update()
		{
			return member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie($this->page->get('owner'));
		}

		public function link_to_update()
		{
			return add_request(get_request(), sprintf('editable_edit=%d#editable%1$d', $this->page->get('id')));
		}

		/* Ugly fix for the companies page, will come up with a solution in the new layout -Martijn */
		public function get_page_id()
		{
			return $this->page->get('id');
		}
	}
