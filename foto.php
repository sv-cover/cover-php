<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	class Controllerfoto extends Controller {
		var $model = null;

		function Controllerfoto() {
			$this->model = get_model('DataModelMember');
		}
		
		/** @get_photo
		  * Returns the (jpg) picture of member with id $lidid.
		  * @lidid the id of the member
		  *
		  * @result A jpg image of the member.
		  */
		function get_photo($lidid) {
			$iter = $this->model->get_iter($lidid);

			run_view('foto::getphoto', $this->model, $iter, null);
		}
		
		/** @group 
		  * Returns a thumbnail of the photo of member
		  * @lidid the id of the member
		  *
		  * @result A jpg image of the memberq
		  */
		function get_thumb($lidid) {
			$iter = $this->model->get_iter($lidid);

			run_view('foto::getthumb', $this->model, $iter, null);
		}
		
		function get_content($view, $iter = null, $params = null) {
			run_view('foto::' . $view, $this->model, $iter, $params);
		}
		
		function run_impl() {
			if ($_GET['lid_id'] == -1)
				$this->get_content('geenfoto');
			elseif ($_GET['lid_id'] == -2)
				$this->get_content('incognito');
			elseif (isset($_GET['get_thumb'])) 
				$this->get_thumb($_GET['lid_id']);
			else
				$this->get_photo($_GET['lid_id']);
		}
	}
	
	$controller = new Controllerfoto();
	$controller->run();
?>

