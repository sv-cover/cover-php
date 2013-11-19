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

		function partial_bestuurslogo()
		{
			$logos = glob('./images/scherm/bestuurslogos/*.{jpg,png}', GLOB_BRACE);

			$logo = $logos[mt_rand(0, count($logos) - 1)];

			$this->get_content('bestuurslogo', null, compact('logo'));
		}

		function partial_maikel()
		{
			$site = file_get_contents('http://maikelzitopdingen.nl/');

			if (!preg_match_all('~<h2>(.+?)</h2><h6>(.+?)</h6><img src="(.+?)"~', $site, $matches, PREG_PATTERN_ORDER))
				return;

			if (count($matches) == 0)
				return;

			$random_maikel = mt_rand(0, count($matches[1]) - 1);

			$data = array(
				'caption' => $matches[1][$random_maikel],
				'src' => $matches[3][$random_maikel]
			);

			$this->get_content('maikel', null, $data);
		}
		
		function run_impl() {
			// We refreshen de inhoud van één slide
			if (isset($_GET['partial']) && method_exists($this, 'partial_' . $_GET['partial']))
			{
				header("Cache-Control: no-cache, must-revalidate");
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
				header("Cache-Control: no-cache, must-revalidate");
				header("Pragma: no-cache");
				call_user_func(array($this, 'partial_' . $_GET['partial']));
			}
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
