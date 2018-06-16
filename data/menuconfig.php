<?php
	include('../include/init.php');

	if (!isset($_GET['menu'])) {
		echo __('There is a problem with the menu, if this keeps occuring, contact the Easy at easy@ai.rug.nl');
		exit;
	}
	
	$menu = $_GET['menu'];

	if (isset($_GET['collapse']))
		do_collapse($menu, $_GET['collapse'] == true);
	else
		echo __('This action cannot be done, if this keeps occuring contact  the easy at easy@ai.rug.nl');
		
	
	function do_collapse($menu, $collapse) {
		$_SESSION['menu_config']['collapse_' . $menu] = $collapse;
	}
?>
