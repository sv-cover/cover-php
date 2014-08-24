<?php

$commissie_model = get_model('DataModelCommissie');

// for debugging purposes
if (isset($_GET['commissie'])) {
	$commissie = $commissie_model->get_iter($_GET['commissie']);
}
// Pick a random commissie
else {
	$commissies = $commissie_model->get(false);

	// Apparently sometimes a commissie is empty?
	while (empty($commissie))
		$commissie = $commissies[mt_rand(0, count($commissies))];
}

$leden = $commissie_model->get_leden($commissie->get('id'));

function _full_name($lid) {
	return $lid->get('tussenvoegsel')
		? sprintf('%s %s %s', $lid->get('voornaam'), $lid->get('tussenvoegsel'), $lid->get('achternaam'))
		: sprintf('%s %s', $lid->get('voornaam'), $lid->get('achternaam'));
}

?>
<div style="text-align: center; width:100%;height:100%">
	<h2 style="font-size: 80px; margin: 80px 0;"><?=markup_format_text($commissie->get('naam'))?></h2>
	<?php foreach ($leden as $lid): ?>
	<div style="display: inline-block; padding: 50px">
		<img src="foto.php?lid_id=<?=$lid->get('id')?>&amp;get_thumb=circle&amp;width=200" width="200" height="200">
		<span style="display: block; font-size: 20px;"><?=markup_format_text(_full_name($lid))?></span>
		<span style="display: block; font-size: 14px;"><?=markup_format_text($lid->get('functie') ? __translate_parts($lid->get('functie'), ',/') : '')?></span>
	</div>
	<?php endforeach ?>
</div>