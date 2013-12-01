<?php

function render_template($__TEMPLATE__, array $__DATA__)
{
	ob_start();
	extract($__DATA__);
	include $__TEMPLATE__;
	return ob_get_clean();
}