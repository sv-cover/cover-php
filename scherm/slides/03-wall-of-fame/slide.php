<?php

$commissie_model = get_model('DataModelCommissie');

$commissies = $commissie_model->get(false);

$commissie = $commissies[mt_rand(0, count($commissies))];

$leden = $commissie_model->get_leden($commissie->get('id'));

function _full_name($lid) {
	return $lid->get('tussenvoegsel')
		? sprintf('%s %s %s', $lid->get('voornaam'), $lid->get('tussenvoegsel'), $lid->get('achternaam'))
		: sprintf('%s %s', $lid->get('voornaam'), $lid->get('achternaam'));
}

?>
<div style="text-align: center; width:100%;height:100%">
	<h2 style="font-size: 80px; margin: 80px 0;"><?=htmlspecialchars($commissie->get('naam'))?></h2>
	<?php foreach ($leden as $lid): ?>
	<div style="display: inline-block; padding: 50px">
		<img src="foto.php?lid_id=<?=$lid->get('id')?>&amp;get_thumb=circle&amp;width=400" width="200" height="200">
		<span style="display: block; font-size: 20px;"><?=htmlspecialchars(_full_name($lid))?></span>
		<span style="display: block; font-size: 14px;"><?=htmlspecialchars($lid->get('functie'))?></span>
	</div>
	<?php endforeach ?>
</div>