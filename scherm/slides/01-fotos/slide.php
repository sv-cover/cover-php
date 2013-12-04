<?php

$model = get_model('DataModelFotoboek');

$boek = $model->get_random_book();

$fotos = $model->get_photos($boek);

?>
<div class="collage">
	<h1><?=$boek->get('titel')?></h1>
	<ul class="collage">
		<? foreach ($fotos as $foto): ?>
		<li class="foto">
			<figure>
				<img src="<?=$foto->get('url')?>">
				<figcaption><?=markup_format_text($foto->get('titel'))?></figcaption>
			</figure>
		</li>
		<? endforeach ?>
	</ul>
</div>
<script>layout_photos(this);</script>