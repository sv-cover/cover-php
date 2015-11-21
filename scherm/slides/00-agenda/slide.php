<?php

$agenda = get_model('DataModelAgenda');

$punten = array_filter($agenda->get_agendapunten(), [get_policy($agenda), 'user_can_read']);

// Only 10 items fit on the screen at the same time.
$punten = array_slice($punten, 0, 10);

?>
<div style="overflow: hidden">
	<h1 style="text-indent: 20px;">Agenda</h1>
	<?php foreach ($punten as $punt): ?>
	<div style="display: block; float: left; width: 840px; height: 140px; margin: 0 20px 0 40px; padding: 20px; border-bottom: 1px solid #ccc">
		<h3><?=markup_format_text($punt->get('kop'))?></h3>
		<span class="date"><?=agenda_period_for_display($punt) ?></span>
		<?php if ($punt->get('locatie')): ?>
			<span class="location">in <?= markup_format_text($punt->get('locatie')) ?></span>
		<?php endif ?>
	</div>
	<?php endforeach ?>
</div>
