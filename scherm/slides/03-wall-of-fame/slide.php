<?php
require_once 'member.php';

$commissie_model = get_model('DataModelCommissie');

// for debugging purposes
if (isset($_GET['commissie'])) {
	$commissie = $commissie_model->get_iter($_GET['commissie']);
}
// Pick a random commissie
else {
	$commissie = $commissie_model->get_random();
}

$leden = $commissie->get_members();

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
	<?php if ($commissie->get('vacancies')): ?>
		<div style="background: #c60c30; color: white; line-height: 64px; position: absolute; bottom: 0; left: 0; right: 0; text-align:center; font-size: 40px;">
			<?php if ($commissioner = $commissie_model->get_lid_for_functie(COMMISSIE_BESTUUR, 'commissaris intern')): ?>
				<img src="foto.php?lid_id=<?=$commissioner->get('id')?>&amp;get_thumb=circle&amp;width=100" width="100" height="100" style="border-radius: 50% float: left; margin: 14px 14px 0 0;">
				<div style="display: inline-block; text-align:left"><?=sprintf(__('Hey! Deze commissie zoekt nieuwe commissieleden.<br>Heb jij interesse? Zeg het de Commissaris Intern, %s (intern@svcover.nl)'), markup_format_text(member_full_name($commissioner))) ?></div>
			<?php else: ?>
				<div style="display: inline-block; text-align:left"><?=__('Hey! Deze commissie zoekt nieuwe commissieleden. Heb jij interesse? Zeg het de Intern via intern@svcover.nl.') ?></div>
			<?php endif ?>
	<?php endif ?>
</div>
