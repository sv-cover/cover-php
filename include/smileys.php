<?php
	if (!defined('IN_SITE'))
		return;

	
	function get_smileys() {
		static $smileys = null;
		
		if ($smileys != null)
			return $smileys;
		
		$smileys = array(
			'\&lt\;\:-\||\&lt\;\:\|' => 'shame.gif',
			'\:-\)|\:\)' => 'smile.gif',
			'\:-\(|\:\(' => 'sad.gif',
			'\;-\)|\;\)' => 'wink.gif',
			'\;-\(|\;\(' => 'cry.gif',
			'\:-\||\:\|' => 'border.gif',
			'\:-@|\:@' => 'yell.gif',
			'8-o|8o' => 'bigeyes.gif',
			'\:-d|\:d' => 'laugh.gif',
			'\:-p|\:p' => 'tongue.gif',
			'\:-s|\:s' => 'sick.gif',
			'x-p' => 'knockout.gif',
			'\[o[oe]ps\]' => 'oops.gif',
			'\[bye\]' => 'bye.gif',
			'\[hug\]' => 'hug.png');
		
		return $smileys;
	}
?>
