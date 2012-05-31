<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	require_once('member.php');
	require_once('form.php');
	require_once('login.php');

	class ControllerTaken extends Controller {
		var $model = null;

		function ControllerTaken() {
			$this->model = get_model('DataModelTaken');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => _('Taken')));
			run_view('taken::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _view_taken() {
			$taken = $this->model->get();
			$this->get_content('taken', $taken);
		}
		
		function _check_toegewezen($name, $value) {
			if (!is_numeric($value))
				return false;
			
			if ($value == 0)
				return null;
			else
				return intval($value);
		}
		
		function _notify_subscribers($iter, $new) {
			$subscribers = $this->model->get_subscribers($iter->get('id'), $new);
			
			if (count($subscribers) == 0)
				return;
			
			$member_model = get_model('DataModelMember');
			$logged_in = logged_in();
			$prioriteiten = $this->model->get_prioriteiten();

			if (!$new) {
				$data = $iter->get_changed_values();
				$data['id'] = $iter->get('id');
			} else {
				$data = $iter->data;
			}

			$data['member_naam'] = member_full_name();
			
			if (isset($data['afgehandeld'])) {
				if ($data['afgehandeld'])
					$data['afgehandeld'] = 'Ja';
				else
					$data['afgehandeld'] = 'Nee';
			}
			
			if (isset($data['prioriteit']))
				$data['prioriteit'] = $prioriteiten[$data['prioriteit']];
			
			$body = parse_email('taken_mod.txt', $data);
			
			if (!$new)
				$subject = 'Veranderde taak ' . $iter->get('taak');
			else
				$subject = 'Nieuwe taak ' . $iter->get('taak');

			foreach ($subscribers as $subscriber) {
				$member_data = $member_model->get_iter($subscriber->get('lidid'));
				
				if (!$member_data || !$member_data->get('email') || $member_data->get('id') == $logged_in['id'])
					continue;

				/* Notify member of the assigned task */
				mail($member_data->get('email'), $subject, $body, "From: webcie@ai.rug.nl\r\n");
			}
		}
		
		function _process_add() {
			$data = check_values(array(
					array('name' => 'toegewezen', 'function' => array(&$this, '_check_toegewezen')),
					'taak',
					'beschrijving',
					array('name' => 'prioriteit', 'function' => 'check_value_toint')
					), $errors);
			
			if (count($errors) != 0) {
				$this->get_content('taken', null, array('errors' => $errors, 'expand' => true));
				return;
			}
			
			$iter = new DataIter($this->model, -1, $data);
			$id = $this->model->insert($iter, true);
			
			if (get_post('subscribed') == 'yes') {
				$member_data = logged_in();
				$subscribe = new DataIter($this->model, -1, array('lidid' => intval($member_data['id']), 'taakid' => intval($id)));
				$this->model->insert_subscribe($subscribe);
			}
			
			$iter->set('id', $id);
			
			$this->_notify_subscribers($iter, true);
			$this->_mail_toegewezen($iter, null, true);

			header('Location: taken.php');
		}
		
		function _mail_toegewezen($iter, $toegewezen, $new) {
			$now = $iter->get('toegewezen');
			
			if (!$now || ($now == $toegewezen && !$new))
				return;
			
			$member_model = get_model('DataModelMember');
			$member_data = $member_model->get_iter($now);
			$logged_in = logged_in();

			if ($member_data->get('id') == $logged_in['id'] || !$member_data->get('email'))
				return;
			
			$subscribers = $this->model->get_subscribers($iter->get('id'), $new);
			
			foreach ($subscribers as $subscriber)
				if ($subscriber->get('lidid') == $member_data->get('id'))
					return;
			
			/* Notify member of the assigned task */
			$data = $iter->data;
			$data['member_naam'] = member_full_name();
			
			$body = parse_email('taken_assign.txt', $data);
			$subject = 'Toegewezen taak ' . $data['taak'];

			mail($member_data->get('email'), $subject, $body, "From: webcie@svcover.nl\r\n");
		}
		
		function _process_edit($id) {
			$member_data = logged_in();
			$iter = $this->model->get_iter($id, $member_data['id']);
			
			if (!$iter) {
				$this->get_content('not_found');
				return;
			}
			
			$data = check_values(array(
					array('name' => 'toegewezen', 'function' => array(&$this, '_check_toegewezen')),
					'taak',
					'beschrijving',
					array('name' => 'prioriteit', 'function' => 'check_value_toint')
					), $errors);
			
			if (count($errors) > 0) {
				$this->get_content('taak', $iter);
				return;
			}
			
			$toegewezen = $iter->get('toegewezen');
			$iter->set_all($data);
			
			if (get_post('afgehandeld') == 'yes')
				$iter->set_literal('afgehandeld', 'NOW()');
			else
				$iter->set('afgehandeld', null);
			
			$this->model->update($iter);
			$changes = $iter->get_changed_values();
			
			if (count($changes) != 0)
				$this->_notify_subscribers($iter, false);		

			if ($iter->get('subscribed') != (get_post('subscribed') == 'yes')) {
				$member_data = logged_in();
				$subscribe = new DataIter($this->model, -1, array('lidid' => intval($member_data['id']), 'taakid' => intval($iter->get('id'))));

				if (get_post('subscribed') == 'yes')
					$this->model->insert_subscribe($subscribe);
				else
					$this->model->delete_subscribe($subscribe);
			}

			$this->_mail_toegewezen($iter, $toegewezen, false);
			header('Location: taken.php?taak=' . $id);
		}
		
		function _view_taak($id) {
			$member_data = logged_in();
			$iter = $this->model->get_iter($id, $member_data['id']);
			if ($iter)
				$this->get_content('taak', $iter);
			else
				$this->get_content('not_found');
		}
		
		function run_impl() {
			if (!member_in_commissie(COMMISSIE_EASY))
				$this->get_content('auth');
			elseif (isset($_POST['submtaken_add']))
				$this->_process_add();
			elseif (isset($_POST['submtaken_edit']))
				$this->_process_edit(get_post('id'));
			elseif (isset($_GET['taak']))
				$this->_view_taak($_GET['taak']);
			else
				$this->_view_taken();
		}
	}
	
	$controller = new ControllerTaken();
	$controller->run();
?>
