<?php

return [
	'broker'            => env('KAFKA_BROKER'),
	'ignore_keyword'    => env('REQUEST_LOG_IGNORE_KEYWORD'),
	'business'          => env('REQUEST_LOG_BUSINESS', 'oak'),
	'write_local_sql'   => env('WRITE_LOCAL_SQL', false),
	'local_sql_channel' => env('LOCAL_SQL_CHANNEL', 'sql')
];
