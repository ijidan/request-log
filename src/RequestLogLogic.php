<?php

namespace IJidan\RequestLog;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * 请求日志
 * Class RequestLogRepo
 * @package App\Repository
 */
class RequestLogLogic {

	/**
	 * topic
	 * @var KafKa
	 */
	private KafKa $kafKaRepo;

	/**
	 * 数据
	 * @var array
	 */
	private array $data = [];

	/**
	 * 构造函数
	 */
	public function __construct() {
		$this->kafKaRepo = new KafKa();
	}

	/**
	 * 发送请求日志
	 * @param Request $request
	 * @param mixed   $response
	 * @return void
	 */
	public function sendRequestLog(Request $request, $response) {
		$headers = $request->headers->all();
		if (isset($headers['authorization'])) {
			unset($headers['authorization']);
		}

		if (isset($headers['x-request-id']) && $headers['x-request-id'][0]) {
			try {
				$user = $request->user('api');
				$userId = is_object($user) ? $user->id : 0;
			}catch (\Exception $exception){
				$userId =0;
			}

			$route = $request->route();

			$type = 'req';
			$log = [
				'request_id'   => $headers['x-request-id'][0],
				'type'         => $type,
				'trigger_time' => microtime(true),
				'business'     => Config::get('request-log.business'),
				'data'         => [
					'user_id'        => $userId,
					'receive_time'   => LARAVEL_START,
					'response_time'  => microtime(true),
					'route_id'       => $request['x-route-id'] ?? '',
					'url_address'    => $request['x-url-address'] ?? '',
					'api_route_uri'  => $route->uri(),
					'api_route_name' => (string)$route->getAction('as'),
					'api'            => $request->path(),
					'method'         => $request->method(),
					'params'         => $this->hideKeywordParam($request),
					'status_code'    => $response->getStatusCode(),
					'response'       => $response->content(),
					'ip'             => $request->ip(),
					'headers'        => $headers,
					'server'         => $request->server->all(),
				]
			];
			$this->pushData($type, $log);
		}

	}

	/**
	 * 隐藏关键字
	 * @param Request $request
	 * @return array
	 */
	private function hideKeywordParam(Request $request): array {
		$param = $request->all();
		$ignoreKeyword = Config::get('request-log.ignore_keyword');
		if ($ignoreKeyword) {
			$ignoreKeywordList = explode(',', $ignoreKeyword);
			foreach ($ignoreKeywordList as $keyword) {
				if ($keyword && isset($param[$keyword])) {
					$param[$keyword] = '***';
				}
			}
		}
		return $param;
	}

	/**
	 * 发送SQL日志
	 * @param string $requestId
	 * @param float  $duration
	 * @param string $realSql
	 * @return void
	 */
	public function sendSqlLog(string $requestId, float $duration, string $realSql) {
		$type = 'sql';
		if ($requestId) {
			$log = [
				'request_id'   => $requestId,
				'type'         => $type,
				'trigger_time' => microtime(true),
				'business'     => Config::get('request-log.business'),
				'data'         => [
					'duration' => $duration,
					'query'    => $realSql
				]
			];
			$this->pushData($type, $log);
		}

	}

	/**
	 * 发送业务日志
	 * @param array  $record
	 * @param string $requestId
	 * @return void
	 */
	public function sendBusinessLog(array $record, string $requestId) {
		$type = 'business';
		if ($requestId) {
			$log = [
				'request_id'   => $requestId,
				'type'         => $type,
				'trigger_time' => microtime(true),
				'business'     => Config::get('request-log.business'),
				'data'         => [
					'message' => $record['message'],
					'context' => $record['context'],
					'extra'   => $record['extra']
				]
			];
			$this->pushData($type, $log);
		}
	}

	/**
	 * push data
	 * @param string $type
	 * @param array  $data
	 * @return void
	 */
	public function pushData(string $type, array $data) {
		if (!isset($this->data[$type])) {
			$this->data[$type] = [];
		}
		$this->data[$type][] = $data;
	}

	/**
	 * 发送消息
	 * @return void
	 */
	public function flush() {
		$this->kafKaRepo->sendMessage($this->data);
		$this->data = [];
	}

	/**
	 * @return void
	 */
	private function __clone() {
		// TODO: Implement __clone() method.
	}

}
