<?php

namespace IJidan\RequestLog;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

/**
 * 请求日志
 * Class RequestLog
 * @package App\Http\Middleware
 */
class WriteRequestLog {

	/**
	 * Handle an incoming request.
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure                 $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next) {
		// 获取request_id
		$requestId = $request->headers->get('x-request-id', (string)Str::uuid());
		$request->headers->set('x-request-id', $requestId);
		$response = $next($request);
		$this->writeLog($request, $response);
		return $response;
	}

	/**
	 * 写SQL日志
	 * @return void
	 */
	private function writeSQLLog() {
		$isLocal = app()->environment('local');
		DB::listen(function (QueryExecuted $query) use ($isLocal) {
			$realSql = vsprintf(str_replace('?', '%s', $query->sql), collect($query->bindings)->map(function ($binding) {
				return is_numeric($binding) ? $binding : "'{$binding}'";
			})->toArray());
			$requestId = \request()->header('x-request-id');
			$duration = $this->formatDuration($query->time / 1000);
			$content = sprintf('[%s] [%s] %s |', $query->connection->getDatabaseName(), $duration, $realSql);

			if ($isLocal) {
				Log::channel('sql')->info($content, [
					'method'     => request()->method(),
					'uri'        => request()->getRequestUri(),
					'request_id' => $requestId
				]);
			}
			RequestLog::sendSqlLog($requestId, (float)$query->time, $realSql);
		});
	}


	/**
	 * 写日志
	 * @param Request $request
	 * @param         $response
	 * @return void
	 */
	private function writeLog(Request $request, $response) {
		if ($response instanceof Response || $response instanceof JsonResponse) {
			RequestLog::sendRequestLog($request, $response);
			RequestLog::flush();
		}
	}


	/**
	 * Format duration.
	 * @param float $seconds
	 * @return string
	 */
	private function formatDuration(float $seconds): string {
		if ($seconds < 0.001) {
			return round($seconds * 1000000) . 'μs';
		} elseif ($seconds < 1) {
			return round($seconds * 1000, 2) . 'ms';
		}
		return round($seconds, 2) . 's';
	}

}
