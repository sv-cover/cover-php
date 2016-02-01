<?php
require_once 'include/member.php';

$commissie_model = get_model('DataModelCommissie');
$commissie_model->type = DataModelCommissie::TYPE_COMMITTEE;

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

function _find_image($search_paths) {
	foreach ($search_paths as $path)
		if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path))
			return $path;
	return null;
}

$commissie_foto = _find_image(array(
	'images/committees/' . $commissie->get('login') . '.gif',
	'images/committees/' . $commissie->get('login') . '.jpg'
));

?>
<div style="text-align: center; width:100%;height:100%; position:relative; overflow:hidden;">
	<?php if($commissie_foto): ?>
		<!-- Committee photo -->
		<div style="position:absolute; top:-30px; right:-30px; bottom:-30px; left:-30px; background:url('<?= $commissie_foto ?>') center/cover no-repeat; filter:blur(20px); -webkit-filter:blur(20px);"></div>
		<div style="position:absolute; top:0; right:0; bottom:0; left:0; background:url('<?= $commissie_foto ?>') center/contain no-repeat; filter:drop-shadow(0 0 100px black); -webkit-filter:drop-shadow(0 0 100px black);">
			<h1 class="text-outline-thick-white" style="font-size: 80px;"><?=markup_format_text($commissie->get('naam'))?></h1>
			<table style="position:absolute; bottom:20px; left:20px; width:auto; color:white; text-align:left; text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);">
			<?php foreach ($leden as $lid): ?>
				<tr>
					<td style="font-size: 20px; text-transform:lowercase; font-variant:small-caps; vertical-align:baseline; text-align:right; padding-right:20px;"><?=markup_format_text($lid->get('functie') ? __translate_parts($lid->get('functie'), ',/') : '')?></td>
					<td style="font-size: 30px; vertical-align:baseline; text-align: left;"><?=markup_format_text(_full_name($lid))?></td>
				</tr>
			<?php endforeach ?>
			</table>
		</div>

	<?php else: ?>
		<!-- Committee faces -->
		<h1 style="font-size: 80px;"><?=markup_format_text($commissie->get('naam'))?></h1>
		<?php foreach ($leden as $lid): ?>
		<div style="display: inline-block; padding: 25px 0; position: relative">
			<div style="position:absolute;width:298px;height:298px;border-radius:50%; border:1px solid rgba(0,0,0,0.1); margin: 0 50px;"></div>
			<img src="foto.php?lid_id=<?=$lid->get('id')?>&amp;format=square&amp;width=300" width="300" height="300" style="border-radius:50%; margin: 0 50px;">
			<span style="display: block; font-size: 30px; line-height: 0.9em;"><?=markup_format_text(_full_name($lid))?></span>
			<span style="display: block; font-size: 20px; text-transform:lowercase; font-variant:small-caps;"><?=markup_format_text($lid->get('functie') ? __translate_parts($lid->get('functie'), ',/') : '')?></span>
		</div>
		<?php endforeach ?>
	<?php endif ?>

 	<?php if ($commissie->get('vacancies')): ?>
		<!-- New committee members banner -->
		<div style="position: absolute; top: 100px; left: -175px; width: 500px; padding: 20px 100px; border-radius: 10px; background:#c60c30; box-shadow: 0 0 0 4px white, 0 0 0 8px #c60c30; font-size: 50px; line-height: 1em; text-align: center; color: white; transform: rotate(-45deg);">
			<?=__('Commissieleden gezocht!') ?>
		</div>
	<?php endif ?> 
</div>
