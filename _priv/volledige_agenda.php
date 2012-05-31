<?php
	include('../include/init.php');
	require_once('member.php');
	require_once('markup.php');

	echo '<html>
		<head>
			<title>Volledige agenda</title>
			<style>
				table {
					border-collapse: collapse;
					border: 1px solid orange;
					width: 100%;
				}
				
				table td {
					padding: 3px;
					vertical-align: top;
				}
				
				td.title {
					font-weight: bold;
				}
				
				tr.border td {
					border-top: 1px solid orange;
					padding-top: 20px;
				}
			</style>
		</head>
		<body>';
	
	if (!member_in_commissie(COMMISSIE_BESTUUR)) {
		echo 'Dit mag jij niet zien';
		echo '</body></html>';
		exit();
	}
	
	$model = get_model('DataModelAgenda');
	$cmodel = get_model('DataModelCommissie');
	
	$iters = $model->get();
	
	echo '<table>';
	
	foreach ($iters as $iter) {
		$commissie = $cmodel->get_iter($iter->get('commissie'));
		echo '<tr class="border"><td class="title">Kop:</td><td>' . $iter->get('kop') . '</td></tr>
		<tr><td class="title">Door:</td><td>';
		if($commissie != NULL) {
			echo $commissie->get('naam');
		}
		echo '</td></tr>
		<tr><td class="title">Van:</td><td>' . $iter->get('van') . '</td></tr>
		<tr><td class="title">Tot:</td><td>' . $iter->get('tot') . '</td></tr>
		<tr><td class="title">Locatie:</td><td>' . $iter->get('locatie') . '</td></tr>
		<tr><td class="title">Beschrijving:</td><td>' . markup_parse($iter->get('beschrijving')) . '</td></tr>';
	}
	
	echo '</table></body>
	</html>';
?>
