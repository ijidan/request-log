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
use IJidan\RequestLog\Facades\RequestLog as FacadesRequest;
use Illuminate\Support\Facades\Config;

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
		$this->writeSQLLog();;
		$response = $next($request);
		$this->writeLog($request, $response);
		return $response;
	}

	/**
	 * 写SQL日志
	 * @return void
	 */
	private function writeSQLLog() {
		$writeLocalSql = Config::get('request-log.write_local_sql');
		$localSqlChannel = Config::get('request-log.local_sql_channel');

		DB::listen(function (QueryExecuted $query) use ($writeLocalSql,$localSqlChannel) {
			$realSql = vsprintf(str_replace('?', '%s', $query->sql), collect($query->bindings)->map(function ($binding) {
				return is_numeric($binding) ? $binding : "'{$binding}'";
			})->toArray());
			$requestId = \request()->header('x-request-id');
			$duration = $this->formatDuration($query->time / 1000);
			$content = sprintf('[%s] [%s] %s |', $query->connection->getDatabaseName(), $duration, $realSql);

			if ($writeLocalSql && $localSqlChannel) {
				Log::channel($localSqlChannel)->info($content, [
					'method'     => request()->method(),
					'uri'        => request()->getRequestUri(),
					'request_id' => $requestId
				]);
			}
			FacadesRequest::sendSqlLog($requestId, (float)$query->time, $realSql);
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
			FacadesRequest::sendRequestLog($request, $response);
			FacadesRequest::flush();
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
