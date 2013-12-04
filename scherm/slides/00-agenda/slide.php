<?php

$agenda = get_model('DataModelAgenda');

$punten = $agenda->get_agendapunten(true);

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
<div class="agenda">
	<h1>Agenda</h1>
	<?php foreach ($punten as $punt): ?>
	<div class="agenda-item">
		<span class="date"><?=slide_format_date($punt) ?></span>
		<?php if ($punt->get('locatie')): ?><span class="location">in <?= markup_format_text($punt->get('locatie')) ?></span><?php endif ?>

		<h3><?=markup_format_text($punt->get('kop'))?></h3>
	</div>
	<?php endforeach ?>
</div>