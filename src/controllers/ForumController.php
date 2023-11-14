<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

class ForumController extends \Controller
{
	public function run_impl()
	{
		$url = edit_url(get_config_value('url_to_forum', 'https://forum.svcover.nl/'), $_GET);
		return $this->view->redirect($url, false, ALLOW_SUBDOMAINS);
	}
}
