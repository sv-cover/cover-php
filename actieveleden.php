<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	require_once('member.php');

	class ControllerActieveLeden extends Controller {
		var $model = null;

		function ControllerActieveLeden() {
			$this->model = get_model('DataModelActieveLeden');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => _('ActieveLeden')));
			run_view('actieveleden::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _process_nieuwe_commissie($commissie) {
			$naam = get_post('naam');
			
			if ($naam == '' || strlen($naam) > 25) {
				$this->get_content('commissie', $commissie, array('errors' => array('naam')));
				return;
			}

			$commissie_model = get_model('DataModelCommissie');			
			$iter = new DataIter($commissie_model, -1, array(
					'naam' => $naam, 
					'nocaps' => strtolower($naam),
					'login' => get_post('login'),
					'website' => get_post('website')));
			
			$id = $commissie_model->insert($iter, true);

			/* Create a page for the commissie */
			$editable_model = get_model('DataModelEditable');
			$page = new DataIter($editable_model, -1, array(
					'owner' => intval($id),
					'titel' => 'Commissiepagina ' . $naam));
			
			$pageid = $editable_model->insert($page, true);
			
			$iter = $commissie_model->get_iter($id);
			$iter->set('page', intval($pageid));
			$commissie_model->update($iter);
			
			header('Location: actieveleden.php?commissie=' . $id);
			exit();
		}
		
		function _process_search_members($search) {
			ob_end_clean();
			
			$members = $this->model->search_members($search);
			$result = '';

			foreach ($members as $member) {
				if ($result != '')
					$result .= "\n";

				$result .= $member->get('id') . "\t" . member_full_name($member);
			}
			
			echo $result;
			exit();
		}
		
		function _process_nieuw_lid($commissie) {
			if (!$commissie) {
				header('Location: actieveleden.php');
				exit();
			}
			
			$lidnr = get_post('lidid');
			$member_model = get_model('DataModelMember');
			
			if (!is_numeric($lidnr) || !($lid = $member_model->get_iter($lidnr))) {
				$this->get_content('commissie', $commissie, array('errors' => array('lidid')));
				return;
			}
			
			if (strlen(get_post('functie')) > 50) {
				$this->get_content('commissie', $commissie, array('errors' => array('functie')));
				return;				
			}
			
			$iter = new DataIter($this->model, -1, array(
				'lidid' => $lid->get('id'),
				'commissieid' => $commissie->get('id'),
				'functie' => get_post('functie')));
			
			$this->model->insert($iter);
			header('Location: actieveleden.php?commissie=' . $commissie->get('id'));
		}
		
		function _process_commissie($commissie) {
			if (!$commissie) {
				header('Location: actieveleden.php');
				exit();
			}

			foreach ($_POST as $key => $value) {
				if (strncmp($key, 'functie_', 8) != 0)
					continue;
				
				$id = substr($key, 8);

				$iter = $this->model->get_iter($id);

				if (get_post('del_' . $id) == 'yes') {
					$this->model->delete($iter);
				} else {
					$iter->set('functie', get_post('functie_' . $id));
					$iter->set('sleutel', get_post('sleutel_' . $id) == 'yes' ? 1 : 0);

					$this->model->update($iter);
				}
			}		
			
			header('Location: actieveleden.php?commissie=' . $commissie->get('id'));
		}
		
		function _process_del_commissie($commissie) {
			if (!$commissie) {
				header('Location: actieveleden.php');
				exit();
			}
			
			$commissie_model = get_model('DataModelCommissie');
			$commissie_model->delete($commissie);
			
			/* Delete editable page */
			$editable_model = get_model('DataModelEditable');
			$page = $editable_model->get_iter($commissie->get('page'));
			
			if ($page)
				$editable_model->delete($page);
			
			/* Delete all active members */
			$this->model->delete_by_commissie($commissie->get('id'));
			
			header('Location: actieveleden.php');
		}
		
		function run_impl() {
			if (!member_in_commissie(COMMISSIE_BESTUUR)) {
				$this->get_content('auth');
				return;
			}
			
			if (isset($_GET['search_members'])) {
				$this->_process_search_members($_GET['search_members']);
				return;
			}
			
			if (isset($_GET['commissie'])) {
				$commissie_model = get_model('DataModelCommissie');
				$commissie = $commissie_model->get_iter($_GET['commissie']);
			} else {
				$commissie = null;
			}
			
			if (isset($_POST['submactieveledennieuwecommissie']))
				$this->_process_nieuwe_commissie($commissie);
			elseif (isset($_POST['submactieveledennieuwlid']))
				$this->_process_nieuw_lid($commissie);
			elseif (isset($_POST['submactieveledencommissie']))
				$this->_process_commissie($commissie);
			elseif (isset($_GET['del']))
				$this->_process_del_commissie($commissie);
			else
				$this->get_content('commissie', $commissie);
		}
	}
	
	$controller = new ControllerActieveLeden();
	$controller->run();
?>
