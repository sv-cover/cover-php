<?php
$model = get_model('DataModelMessage');
?>
<p style="font: 44px sans-serif;">Send in a message through https://www.svcover.nl/message.php</p>
<ul>
<?php foreach ($model->get_latest(10) as $message): ?>
	<li style="font: 44px sans-serif;"><?=str_ireplace('cover', '<strong style="color:#c60c30">Cover</strong>', markup_format_text($message->message)) ?></li>
<?php endforeach ?>
</ul>