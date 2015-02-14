<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/controllers/ControllerCRUD.php';
	
	class ControllerActieveLeden extends ControllerCRUD
	{
		public function __construct()
		{
			$this->model = get_model('DataModelActieveLeden');
		}

		protected function _get_title($iters = null)
		{
			return __('Geschiedenis van actieve leden');
		}

		public function link_to_read(DataIter $iter)
		{
			return $this->link_to_index() . '#membership' . $iter->get_id();
		}

		protected function _update(DataIter $iter, $data, array &$errors)
		{
			if (isset($data['started_on']) && empty($data['started_on']))
				$data['started_on'] = null;

			if (isset($data['discharged_on']) && empty($data['discharged_on']))
				$data['discharged_on'] = null;
			
			return parent::_update($iter, $data, $errors);
		}
	}
	
	$controller = new ControllerActieveLeden();
	$controller->run();
