<?php
	include('include/init.php');
	include('controllers/Controller.php');
	include('member.php');
	include('form.php');
	
	class ControllerNieuwlid extends Controller {
		var $model = null;
		var $sizes = null;

		function ControllerNieuwlid() {
			$this->model = get_model('DataModelMember');
			
			$this->sizes = array(
				'voornaam' => 255,
				'tussenvoegsel' => 255,
				'achternaam' => 255,
				'adres' => 255,
				'postcode' => 7,
				'woonplaats' => 255,
				'email' => 255,
				'telefoonnummer' => 20);
			
			$this->optional = array(
				'tussenvoegsel', 
				'telefoonnummer');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Nieuw lid')));
			run_view('nieuwlid::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _check_empty($name, $value) {
			if (!in_array($name, $this->optional))
				return check_value_empty($name, $value);
			else
				return $value;
		}
		
		function _check_size($name, $value) {
			if (!isset($this->sizes[$name]))
				return $this->_check_empty($name, 
							   trim($value));

			if (strlen(trim($value)) > $this->sizes[$name])
				return false;
			else
				return $this->_check_empty($name, 
							   trim($value));
		}
		
		function _check_beginjaar($name, $value) {
			if (!is_numeric($value))
				return false;
			
			return intval($value);
		}
		
		function _check_geboortedatum($name, $value) {
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
				return $value;

			if (!get_post('day') || !get_post('month') || 
			    !get_post('year'))
				return false;
			
			if (!is_numeric(get_post('day')) ||
			    !is_numeric(get_post('month')) ||
			    !is_numeric(get_post('year')))
				return false;
			
			$day = intval(get_post('day'));
			$month = intval(get_post('month'));
			$year = intval(get_post('year'));
			
			return $year . '-' . $month . '-' . $day;
		}
		
		function _check_geslacht($name, $value) {
			$value = strtolower($value);

			if ($value == 'f')
				$value = 'v';
			
			if ($value != 'm' && $value != 'v' && $value != 'o')
				return false;
			
			return $value;
		}
		
		function _check_id($name, $value) {
			if (!is_numeric($value))
				return false;
			
			$value = intval($value);
			
			if ($this->model->exists($value))
				return false;
			
			return $value;
		}

		public function process_nieuwlid(array $input, array &$errors)
		{
			$check_size = array($this, '_check_size');
			
			$data = check_values(array(
					array('name' => 'id',
						'function' => array($this, 
						 '_check_id')),
					array('name' => 'voornaam', 
					      'function' => $check_size),
					array('name' => 'tussenvoegsel',
					      'function' => $check_size),
					array('name' => 'achternaam',
					      'function' => $check_size),
					array('name' => 'adres',
					      'function' => $check_size),
					array('name' => 'postcode',
					      'function' => $check_size),
					array('name' => 'woonplaats',
					      'function' => $check_size),
					array('name' => 'email',
					      'function' => $check_size),
					array('name' =>'telefoonnummer',
					      'function' => $check_size),
					
					array('name' => 'beginjaar',
					      'function' => array($this, 
						'_check_beginjaar')),
					array('name' => 'geboortedatum',
					      'function' => array($this,
						'_check_geboortedatum')),
					array('name' => 'geslacht',
					      'function' => array($this,
						'_check_geslacht'))
				), $errors, $input);

			// Check if e-mail is already in use (then it cannot be used for login)
			$active_member_types = array(
				MEMBER_STATUS_LID,
				MEMBER_STATUS_LID_ONZICHTBAAR,
				MEMBER_STATUS_ERELID,
				MEMBER_STATUS_DONATEUR);

			if (($member = $this->model->get_from_email($data['email']))
				&& in_array($member->get('type'), $active_member_types))
				$errors[] = 'email';

			if (count($errors) > 0)
				return false;

			// Create new member
			$iter = new DataIter($this->model, -1, $data);
			$iter->set('privacy', 958698063);
			
			$this->model->insert($iter);
			$id = $data['id'];
			
			// Create profile for this member
			$passwd = create_pronouncable_password();
			$nick = member_full_name($iter, true, false);
			
			if (strlen($nick) > 50)
				$nick = $iter->get('voornaam');
			
			if (strlen($nick) > 50)
				$nick = '';
			
			$iter = new DataIter($this->model, -1, 
					array('lidid' => $id,
					      'wachtwoord' => md5($passwd),
					      'nick' => $nick));
			
			$this->model->insert_profiel($iter);
			
			// Setup e-mail
			$data['wachtwoord'] = $passwd;
			$mail = parse_email('nieuwlid.txt', $data);

			mail($data['email'], 'Website Cover', $mail, 'From: Cover <bestuur@svcover.nl>');
			mail('administratie@svcover.nl', 'Website Cover', $mail, 'From: Cover <bestuur@svcover.nl>');
			
			return $id;
		}

		protected function map_data_to_form_fields(array $data)
		{
			return array(
				'id' => $data['id'],
				'voornaam' => $data['first_name'],
				'tussenvoegsel' => $data['family_name_preposition'],
				'achternaam' => $data['family_name'],
				'adres' => $data['street_name'],
				'postcode' => $data['postal_code'],
				'woonplaats' => $data['place'],
				'email' => $data['email_address'],
				'telefoonnummer' => $data['phone_number'],
				'beginjaar' => $data['year_of_enrollment'],
				'geboortedatum' => $data['birth_date'],
				'geslacht' => $data['gender']
			);
		}
		
		function run_impl() {
			if (!member_in_commissie(COMMISSIE_BESTUUR))
				return $this->get_content('auth');
			
			if (isset($_POST['submnieuwlid'])) {
				$errors = array();

				if (($id = $this->process_nieuwlid($_POST, $errors)) !== false)
					header('Location: nieuwlid.php?success=' . $id);
				else
					$this->get_content('nieuwlid', null, compact('errors'));
			}
			elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
				$fh = fopen($_FILES['csv_file']['tmp_name'], 'rb');

				$headers = fgetcsv($fh, ',');

				while ($line = fgetcsv($fh, ',')) {
					$errors = array();
					
					$data = $this->map_data_to_form_fields(array_combine($headers, $line));

					$this->process_nieuwlid($data, $errors);

					$result[$data['id']] = $errors;
				}

				$this->get_content('import', null, compact('result'));
			}
			else {
				$params = array();
				
				if (isset($_GET['success']))
					$params['message'] = 'Het nieuwe lid is toegevoegd. Je kunt zijn/haar <a href="profiel.php?lid=' . $_GET['success'] . '">profiel</a> bekijken.';

				$this->get_content('nieuwlid', null, $params);
			}
		}
	}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {	
	$controller = new ControllerNieuwlid();
	$controller->run();
}
?>
