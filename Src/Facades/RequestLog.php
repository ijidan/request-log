<?php

namespace IJidan\RequestLog\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 请求日志
 * Class RequestLogRepo
 * @package App\Repository
 * @method static sendSqlLog(string $requestId, float $param, string $realSql)
 * @method static sendBusinessLog(array $record, string $getRequestId)
 * @method static sendRequestLog(\Illuminate\Http\Request $request, \Illuminate\Http\JsonResponse|\Illuminate\Http\Response $response)
 * @method static flush()
 */
class RequestLog extends Facade {

    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string {
        return 'request_log';
    }

}
