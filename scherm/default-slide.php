<?php

$posters = glob('./*.{jpg,png}', GLOB_BRACE);

$poster = $posters[mt_rand(0, count($posters) - 1)];
?>
<img src="<?=$this->link_resource($poster)?>" width="100%">