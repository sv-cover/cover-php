<?php
	require_once 'include/init.php';
	require_once 'include/form.php';
	require_once 'include/controllers/ControllerCRUD.php';
	require_once 'include/controllers/ControllerEditable.php';
	
	class ControllerShow extends ControllerCRUD
	{
		public function __construct()
		{
			$this->model = get_model('DataModelEditable');
		}
		
		protected function _get_title($iter = null)
		{
			if ($iter instanceof DataIter)
				return $iter->get_title();
			else
				return __('Inhoudbeheer');
		}
		
		protected function _is_embedded_page(DataIterEditable $page, $model)
		{
			return get_model($model)->get_from_page($page->get_id());
		}

		public function run_preview()
		{
			$this->embedded = true;

			ob_end_clean();
			
			$language = get_post('editable_language');
			$field = 'content';
			
			if ($language != 'nl')
				$field .= '_' . $language;

			$iter = $this->model->get_iter(get_post('editable_id'));

			$iter->set($field, get_post($field));
			
			$this->get_content('preview', $this->model, $iter, compact('field'));
		}

		public function run_read(DataIter $iter)
		{
			if ($committee = $this->_is_embedded_page($iter, 'DataModelCommissie'))
				$this->redirect('commissies.php?id=' . $committee->get('login'), true);

			else if ($board = $this->_is_embedded_page($iter, 'DataModelBesturen'))
				$this->redirect('besturen.php#' .  rawurlencode($board->get('login')), true);

			else
				return parent::run_read($iter);
		}
	}
	
	$controller = new ControllerShow();
	$controller->run();
