<?php header('Content-Type: text/html; charset=UTF-8') ?>
<!DOCTYPE html>
<html>
	<head>
		<title>Cover Newsletter Archive</title>
	</head>
	<body>
		<ul>
		<?php
		foreach (glob("*.html") as $file)
		{
			if (preg_match('{<title>(.+?)</title>}', file_get_contents($file), $match))
				$title = $match[1];
			else
				$title = utf8_encode(basename($file, '.html'));
			
			printf('<li><a href="%s">%s</a></li>',
				urlencode($file), htmlspecialchars($title, ENT_COMPAT, 'utf-8'));
		}
		?>
		</ul>
	</body>
</html>
