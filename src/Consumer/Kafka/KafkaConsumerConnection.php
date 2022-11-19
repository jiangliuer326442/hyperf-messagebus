<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Consumer\Kafka;

use Mustafa\Messagebus;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Contract\PoolInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use longlang\phpkafka\Consumer\Consumer;
use longlang\phpkafka\Consumer\ConsumerConfig;
use Psr\Container\ContainerInterface;

class KafkaConsumerConnection extends BaseConnection implements ConnectionInterface, MessageBus\Consumer\Interfaces\ConsumerInterface
{
    //消费者组池子
    private array $consumer_kafka_arr;

    private string $current_consumer;

    private array $message;

    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected ConfigInterface $config;

    public function __construct(
        protected PoolInterface $pool,
        protected string $model_name,
        protected array $consumers
    ) {
        parent::__construct($this->container, $this->pool);

        $this->reconnect();
    }

    public function getActiveConnection()
    {
        if ($this->check()) {
            return $this;
        }
        if (!$this->reconnect()) {
            throw new ConnectionException('Connection reconnect failed.');
        }

        return $this;
    }

    public function reconnect(): bool
    {
        foreach ($this->consumers as $consumer_name => $topics_config) {
            $topics_arr = $topics_config['topics'];
            $config = new ConsumerConfig();
            $config->setBroker($this->config->get('messagebus.default.drivers.' . MessageBus\Constants\MessageBusType::KAFKA . '.host'));
            $config->setTopic($topics_arr);
            $config->setAutoCommit(false);
            $config->setGroupId($this->model_name . '-' . $consumer_name);
            $config->setClientId($this->model_name . '-' . $consumer_name . '-' . php_uname('n'));
            $config->setGroupInstanceId($this->model_name . '-' . $consumer_name . '-' . php_uname('n'));
            $this->consumer_kafka_arr[$consumer_name] = new Consumer($config);
        }
        return true;
    }

    public function close(): bool
    {
        unset($this->consumer_kafka_arr);

        return true;
    }

    public function consume(string $consumer, array $events): array
    {
        $this->current_consumer = "";
        $this->message[$consumer] = $this->consumer_kafka_arr[$consumer]->consume();
        if ($this->message[$consumer] && $this->message[$consumer]->getValue()) {
            $this->current_consumer = $consumer;
            if (in_array($this->message[$consumer]->getHeaders()[0]->getValue(), $events)) {
                return [make(MessageBus\Messages\Structs\MessageStruct::class)->loadArray(unserialize($this->message[$consumer]->getValue()))];
            }
        }
        return [];
    }

    public function commit(): bool
    {
        if ($this->current_consumer && $this->consumer_kafka_arr[$this->current_consumer] && $this->message[$this->current_consumer]) {
            $this->consumer_kafka_arr[$this->current_consumer]->ack($this->message[$this->current_consumer]);
            return true;
        }
        return false;
    }
}
