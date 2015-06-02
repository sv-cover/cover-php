<?php

include dirname(__FILE__) . '/json.php';

$fh = fopen('php://output', 'wb');
$js = new JSONWriter($fh);

$js->startObject();

$js->key('four');
$js->value(4);

$js->key('bool');
$js->value(false);

$js->key('list');
$js->startArray();

$js->value(1);
$js->value('two');
$js->value(null);

$js->endArray();

$js->key('object');
$js->startObject();

$js->key('prop');
$js->value('value');

$js->key('embedded_json');
$js->value(['a' => 1, 'b' => 2, 'c' => [3.1, 3.2, 3.3]]);

$js->endObject();
$js->endObject();

assert('$js->isClosed()');

fclose($fh);