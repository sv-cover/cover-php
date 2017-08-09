<?php

$dbids = [
	'docker' => [
		'host' => $_ENV['POSTGRES_HOST'],
		'user' => $_ENV['POSTGRES_USER'],
		'password' => $_ENV['POSTGRES_PASSWORD'],
		'database' => $_ENV['POSTGRES_DB']
	]
];
