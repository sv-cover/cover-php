<?php

$agenda = get_model('DataModelAgenda');

$punten = $agenda->get_agendapunten(true);

// Only 10 items fit on the screen at the same time.
$punten = array_slice($punten, 0, 10);

function slide_format_date($punt)
{
	$days = get_days();
	$months = get_months();

	return sprintf('%s %d %s, %d:%02d',
		$days[$punt->get('vandagnaam')],
		$punt->get('vandatum'),
		$months[$punt->get('vanmaand')],
		$punt->get('vanuur'), $punt->get('vanminuut'));
}

?>
<div style="overflow: hidden">
	<h1 style="text-indent: 20px;">Agenda</h1>
	<?php foreach ($punten as $punt): ?>
	<div style="display: block; float: left; width: 840px; height: 140px; margin: 0 20px 0 40px; padding: 20px; border-bottom: 1px solid #ccc">
		<h3><?=markup_format_text($punt->get('kop'))?></h3>
		<span class="date"><?=slide_format_date($punt) ?></span>
		<?php if ($punt->get('locatie')): ?>
			<span class="location">in <?= markup_format_text($punt->get('locatie')) ?></span>
		<?php endif ?>
	</div>
	<?php endforeach ?>
</div>