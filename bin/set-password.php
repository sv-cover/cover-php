#!/usr/bin/env php
<?php

require 'src/framework/init.php';

function input($prompt) {
	echo $prompt;
	return stream_get_line(STDIN, 1024, PHP_EOL);
}

$member_model = get_model('DataModelMember');

$member_id = (int) trim(input('member id: '));

$member = $member_model->get_iter($member_id);

$password = trim(input('password: '));

$member_model->set_password($member, $password);

echo "done\n";