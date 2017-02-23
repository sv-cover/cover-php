<?php
	include('../include/init.php');

	if (!isset($_GET['menu'])) {
		echo __('Er is geen menu opgegeven, mocht dit vaker voorkomen neem dan contact op met easy@ai.rug.nl');
		exit;
	}
	
	$menu = $_GET['menu'];

	if (isset($_GET['collapse']))
		do_collapse($menu, $_GET['collapse'] == true);
	else
		echo __('Deze actie kan niet worden behandeld, mocht dit vaker voorkomen neem dan contact op met easy@ai.rug.nl');
		
	
	function do_collapse($menu, $collapse) {
		$_SESSION['menu_config']['collapse_' . $menu] = $collapse;
	}
?>
