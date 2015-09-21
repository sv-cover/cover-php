<?php
$model = get_model('DataModelMessage');
?>
<p>Send in a message through https://www.svcover.nl/message.php</p>
<ul>
<?php foreach ($model->get_latest(10) as $message): ?>
	<li><?=markup_format_text($message->message) ?></li>
<?php endforeach ?>
</ul>