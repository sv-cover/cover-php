<?php
	if (!defined('IN_SITE'))
		return;

	require_once('Controller.php');
	require_once('include/editable.php');

	/** 
	  * A class implementing the editable page controller. This class 
	  * is a full controller for editable pages and can be used by other
	  * controllers to embed an editable page. The embedding is fully
	  * automated and the controller embedding this controller only
	  * needs to instantiate and run a #ControllerEditable (great!)
	  */
	class ControllerEditable extends Controller {
		/**
		  * ControllerEditable constructor
		  * @id the id (or title) of the editable page
		  */
		function ControllerEditable($id) {
			$this->model = get_model('DataModelEditable');
			$this->id = $id;
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
		
		function _get_pagenr() {
			if (isset($_POST['editable_pagenr']))
				return intval(get_post('editable_pagenr'));
			elseif (isset($_GET['editable_pagenr']))
				return intval($_GET['editable_pagenr']);
			else
				return 0;
		}
		
		function _get_content_field() {
			$language = $this->_get_language();

			if ($language == 'nl')
				return 'content';
			else
				return 'content_' . $language;
		}
		
		/**
		  * Function that performs common preprocessing before actions
		  * like add, delete save. The function checks if the
		  * currently logged in member has write access to the page
		  * , parses the page number and splits the pages
		  * @iter a #DataIter of the editable page
		  * @pagenr reference; location of the page number
		  * @pages reference; location of the pages array
		  *
		  * @result true if all preprocessing went fine, false
		  * otherwise. This function already gets the content for
		  * the view showing the error when it returns false
		  */
		function _page_prepare($iter, &$pagenr, &$pages, &$field) {
			if (!member_in_commissie($iter->get('owner')) && !member_in_commissie(COMMISSIE_BESTUUR)) {
				if (isset($_GET['xmlrequest'])) {
					ob_end_clean();
				
					echo _('Deze pagina kan niet door jou worden bewerkt.');
					exit();
				}

				$this->get_content('read_only');
				return false;
			}

			$pagenr = $this->_get_pagenr();
			$language = $this->_get_language();
			$field = $this->_get_content_field();
			
			/* Just being paranoid here */
			if (!in_array($field, array_keys($iter->data))) {
				if (isset($_GET['xmlrequest'])) {
					ob_end_clean();
					
					echo _('Er zit iets niet goed met de taalinstelling. Neem contact op met de WebCie');
					exit();
				}
				
				$this->get_content('something_went_wrong', null, array('message' => sprintf(_('Er zit iets niet goed met de taalinstelling. Neem contact op met %s'), '<a href="mailto:webcie@ai.rug.nl">' . _('de Webcie') . '</a>')));
				return false;
			}

			$pages = editable_split_pages($iter->get($field));
			
			if ($pagenr < 0 || $pagenr >= count($pages)) {
				if (isset($_GET['xmlrequest'])) {
					ob_end_clean();
					
					echo _('Er zit iets niet goed met de paginanummering. Neem contact op met de WebCie');
					exit();
				}
				
				$this->get_content('something_went_wrong', null, array('message' => sprintf(_('Er zit iets niet goed met de paginanummering. Neem contact op met %s'), '<a href="mailto:webcie@ai.rug.nl">' . _('de WebCie') . '</a>')));
				return false;	
			}

			return true;	
		}
		
		/**
		  * Joins an array of pages together with appropriate 
		  * [page][/page] markup
		  * @pages an array of pages
		  *
		  * @result a string with all the pages
		  */
		function _pages_join($pages) {
			return (count($pages) > 1 ? ('[page]' . implode('[/page][page]', $pages) . '[/page]') : $pages[0]);
		}
		
		function _prepare_mail($iter, $pagenr, $page) {
			$data = $iter->data;
			$data['member_naam'] = member_full_name();
			$data['pagenr'] = $pagenr;
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
			if (!$this->_page_prepare($iter, $pagenr, $pages, $field))
				return;
			
			$page = false;
			
			if (isset($_GET['xmlrequest']))
				$page = iconv("UTF-8", "ISO-8859-15", get_post($field));
			
			if (!$page)
				$page = get_post($field);

			$pages[$pagenr] = $page;

			$iter->set($field, $this->_pages_join($pages));
			$success = $this->model->update($iter);

			if ($success !== null)
			{
				$data = $this->_prepare_mail($iter, $pagenr + 1, get_post($field));
				
				if ($data['email']) {
					$body = parse_email('editable_edit.txt', $data);
					$subject = 'Pagina ' . $data['titel'] . ' (' . ($pagenr + 1) . ') gewijzigd';

					foreach ($data['email'] as $email)
						mail($email, $subject, $body, "From: webcie@ai.rug.nl\r\n");
				}
			}

			if (isset($_GET['xmlrequest']))
			{
				ob_end_clean();
				
				if ($success !== null)
					printf(_('De pagina %s (%d) is opgeslagen.'), $iter->get('titel'), $pagenr + 1);
				else
					echo $this->model->db->get_last_error();

				exit();
			}

			if ($success !== null)
				$_SESSION['alert'] = sprintf(_('De pagina %s (%d) is opgeslagen.'), $iter->get('titel'), $pagenr + 1);
			else
				$_SESSION['alert'] = $this->model->db->get_last_error();

			header('Location: ' . add_request(get_request(), 'editable_edit&editable_language=' . $this->_get_language()));
			exit();
		}
	
		/**
		  * Adds a new empty page to an editable page
		  * @iter a #DataIter of the editable page
		  */
		function _do_add($iter) {
			if (!$this->_page_prepare($iter, $pagenr, $pages, $field))
				return;
			
			for ($i = count($pages) - 1; $i > $pagenr; $i--)
				$pages[$i + 1] = $pages[$i];

			$pages[$pagenr + 1] = "";

			$iter->set($field, $this->_pages_join($pages));
			$this->model->update($iter);

			header('Location: ' . add_request(get_request('editable_add', 'editable_pagenr', 'editable_language'), 'editable_edit&editable_pagenr=' . ($pagenr + 1) . '&editable_language=' . $this->_get_language()));
			exit();
		}

		/**
		  * Deletes a page from an editable page
		  * @iter a #DataIter of the editable page
		  */
		function _do_del($iter) {
			if (!$this->_page_prepare($iter, $pagenr, $pages, $field))
				return;
			
			if (count($pages) == 1) {
				$_SESSION['alert'] = _('Er is maar een pagina. Maak eerst een nieuwe pagina aan voordat je deze verwijdert.');
				header('Location: ' . add_request(get_request('editable_del'),  'editable_edit'));
				exit();
			}
			
			$page = $pages[$pagenr];
			unset($pages[$pagenr]);
			
			$iter->set($field, $this->_pages_join($pages));
			$this->model->update($iter);
			
			$data = $this->_prepare_mail($iter, $pagenr + 1, $page);
			
			if ($data['email']) {
				$body = parse_email('editable_del.txt', $data);
				$subject = 'Pagina ' . $data['titel'] . ' (' . ($pagenr + 1) . ') verwijderd';
				
				foreach ($data['email'] as $email)
					mail($email, $subject, $body, "From: webcie@ai.rug.nl\r\n");
			}
			
			header('Location: ' . add_request(get_request('editable_del', 'editable_pagenr', 'editable_language'),  'editable_edit&editable_pagenr=' . min($pagenr, count($pages) - 1) . '&editable_language=' . $this->_get_language()));
			
			exit();
		}
		
		/**
		  * Views the editable page in edit mode
		  * @iter a #DataIter of the editable page
		  */
		function _view_edit($iter) {
			$params = array('language' => $this->_get_language());

			if (!member_in_commissie($iter->get('owner')))
				$this->get_content('read_only', $iter, $params);
			else
				$this->get_content('edit', $iter, $params);
		}
		
		function _view_editable($page) {
			$pagenr = $this->_get_pagenr();
			$language = $this->_get_language();
			$field = $this->_get_content_field();

			if ($language != 'nl' && !$page->get($field)) {
				$language = 'nl';
				$field = 'content';
			}

			$pages = editable_parse($page->get($field), $page->get('owner'));
			$pagenr = max(0, min(count($pages) - 1, $pagenr));

			$params = array('pagenr' => $pagenr, 'pages' => $pages, 'language' => $language, 'field' => $field);
			
			$this->get_content('editable', $page, $params);
		}
		
		/**
		  * Runs the editable page. This function will handle all
		  * the actions (add, delete, edit, show) of the editable
		  * page transparently to the controller embedding the
		  * page
		  */
		function run() {
			if (is_numeric($this->id))
				$page = $this->model->get_iter($this->id);
			else
				$page = $this->model->get_iter_from_title($this->id);
			
			if (!$page) {
				if (isset($_GET['xmlrequest'])) {
					ob_end_clean();
				
					echo _('Deze pagina bestaat niet...');
					exit();
				}
				
				$this->get_content('editable', $page);
			} elseif (isset($_POST['submeditable']))
				$this->_do_save($page);
			elseif (isset($_GET['editable_add']))
				$this->_do_add($page);
			elseif (isset($_GET['editable_del']))
				$this->_do_del($page);
			elseif (isset($_GET['editable_edit']))
				$this->_view_edit($page);
			else
				$this->_view_editable($page);
		}
	}
?>
