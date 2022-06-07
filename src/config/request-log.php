<?php

return [
	'broker'         => env('KAFKA_BROKER'),
	'ignore_keyword' => env('REQUEST_LOG_IGNORE_KEYWORD'),
	'business'       => env('REQUEST_LOG_BUSINESS','oak')
];
