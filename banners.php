<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	require_once('member.php');

	class BannerController extends Controller {
		var $model = null;

		function BannerController() {
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => _('Advertenties')));
			run_view('banners::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function get_json_config()
		{
			//check for config file
			if (($json = file_get_contents('themes/default/views/banners/config.json')) != false)
			{
				//decode to 10 levels deep (or some other large number) and return
				return json_decode($json, true, 10);
			}
			else
			{
				return false;
			}
		}

		function get_banners()
		{
			$banners = array();

			foreach($this -> get_json_config() as $banner_config)
			{
				$data = json_decode(file_get_contents($banner_config['location'] . 'data.json'), 10);

				array_push($banners, array('rotator-name' => $banner_config['name'], 'data' => $data, 'location' => $banner_config['location']));
			}
			return $banners;
		}

		function run_impl() {
			if (!member_in_commissie(COMMISSIE_BESTUUR)) {
				$this->get_content('auth');
				return;
			}
			else
			{
				$this -> get_content('banners', array('banners' => $this -> get_banners()));
			}
		}
	}
	
	$controller = new BannerController();
	$controller->run();
?>
