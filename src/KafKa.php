<?php


namespace IJidan\RequestLog;

use Illuminate\Support\Facades\Config;
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * kafka
 * Class KafKaRepo
 * @package App\Repository
 */
class KafKa {

    const TOPIC_LOG_XM = 'oak-log-request';

    /**
     * 地址
     * @var string
     */
    protected string $host;

    /**
     * 端口
     * @var int
     */
    protected int $port;

    /**
     * producer
     * @var Producer
     */
    protected Producer $producer;

    /**
     * topic
     * @var ProducerTopic
     */
    protected ProducerTopic $topic;

    /**
     * 构造函数
     */
    public function __construct($topic = self::TOPIC_LOG_XM) {
        $broker = Config::get('request-log.broker');
        $conf = new  Conf();
        $conf->set('metadata.broker.list', $broker);
        $conf->set('request.required.acks', -1);
	    $conf->set('log_level',0);
        try {
            $this->producer = new Producer($conf);
            $this->topic = $this->producer->newTopic($topic);
        } catch (Exception $exception) {
        }

    }

    /**
     * 发送消息
     * @param array $messageData
     * @return void
     */
    public function sendMessage(array $messageData) {
        if ($messageData) {
	        $timeout = Config::get('request-log.send_timeout');
            try {
                $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($messageData));
                $this->producer->poll(0);
                for ($flushRetries = 0; $flushRetries < 1; $flushRetries++) {
                    $result = $this->producer->flush($timeout);
                    if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                        break;
                    }
                }
                if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
                    Log::warning("kafka unable to flush,message might be lost!");
                }
            } catch (Exception $exception) {
                Log::warning($exception->getMessage());
            }

        }
    }


}
