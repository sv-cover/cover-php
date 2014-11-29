<?php

$model = get_model('DataModelFotoboek');

$boek = $model->get_random_book();

$fotos = $model->get_photos($boek);

shuffle($fotos);

?>
<div class="collage">
	<h1><?=$boek->get('titel')?></h1>
	<ul class="flow-gallery">
		<? foreach (array_slice($fotos, 0, 30) as $foto): ?>
		<li class="foto">
			<img src="<?=$foto->get('url')?>" height="<?=$foto->get('height')?>" width="<?=$foto->get('width')?>">
			<span class="description"><?=markup_format_text($foto->get('beschrijving'))?></span>
		</li>
		<? endforeach ?>
	</ul>
</div>
<script>layout_photos();</script>
