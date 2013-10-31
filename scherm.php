<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	class ControllerScherm extends Controller {
		
		function ControllerScherm() {
		
		}
		
		function get_content($view, $iter = null, $params = null) {
			run_view('scherm::' . $view, null, $iter, $params);
		}

		function partial_fotos()
		{
			$model = get_model('DataModelFotoboek');

			$boek = $model->get_random_book();

			$fotos = $model->get_photos($boek);

			$this->get_content('fotos', $fotos, compact('boek'));
		}

		function partial_agenda()
		{
			$agenda = get_model('DataModelAgenda');
			$this->get_content('agenda', $agenda->get_agendapunten(true));
		}
		
		function run_impl() {
			// We refreshen de inhoud van één slide
			if (isset($_GET['partial']) && method_exists($this, 'partial_' . $_GET['partial']))
				call_user_func(array($this, 'partial_' . $_GET['partial']));
			// Moeten we de gehele pagina opnieuw laden? Soort restart-knopje
			elseif (isset($_GET['latest_version']))
				echo '0';
			// Laad de pagina
			else
				$this->get_content('scherm');
		}
	}
	
	$controller = new ControllerScherm();
	$controller->run();
?>
