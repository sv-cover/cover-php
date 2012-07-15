<?php
	function _($m)
	{
		return $m;
	}

	function ngettext($first, $second, $num)
	{
		return $num <= 1 ? $first : $second;
	}
?>
